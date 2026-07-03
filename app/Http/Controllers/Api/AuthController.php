<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OlCustomerResetPasswordMail;
use App\Mail\OlCustomerVerifyEmailMail;
use App\Models\OlCustomer;
use App\Models\OlCustomerSocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Email/password registration. One identity per email: if the email already
     * exists we return a soft "link" hint instead of erroring, so the user is
     * guided to Google + set-password rather than hitting a dead end.
     *
     * Note: this intentionally reveals that an email is registered (acceptable for
     * a signup form). The forgot-password flow, by contrast, is enumeration-safe.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
            'phone_number' => 'nullable|string|max:20',
        ]);

        if (OlCustomer::where('email', $request->email)->exists()) {
            return response()->json([
                'message' => 'Email ini sudah terdaftar. Masuk dengan Google lalu setel password di Pengaturan, atau gunakan tautan "Lupa password".',
                'error_code' => 'account_exists',
            ], 422);
        }

        $customer = OlCustomer::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'email_verified_at' => null,
        ]);

        $this->sendVerificationEmail($customer);

        $token = $customer->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->serializeCustomer($customer),
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $customer = OlCustomer::where('email', $request->email)->first();

        if (! $customer) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan salah.'],
            ]);
        }

        // Passwordless (e.g. Google-only) account attempting password login.
        if (is_null($customer->password)) {
            return response()->json([
                'message' => 'Akun ini masuk lewat Google. Lanjutkan dengan Google, atau setel password lewat tautan "Lupa password".',
                'error_code' => 'oauth_only',
            ], 422);
        }

        if (! Hash::check($request->password, $customer->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan salah.'],
            ]);
        }

        $token = $customer->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->serializeCustomer($customer),
        ]);
    }

    public function handleGoogleCallback(Request $request)
    {
        Log::info('Google OAuth Handshake Started', [
            'payload' => $request->all(),
            'ip' => $request->ip(),
        ]);

        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'google_id' => 'required|string',
                'name' => 'nullable|string|max:255',
                'avatar' => 'nullable|string',
            ]);

            [$customer, $isNew] = $this->resolveGoogleLogin(
                $validated['google_id'],
                $validated['email'],
                $validated['name'] ?? null,
                $validated['avatar'] ?? null,
            );

            $token = $customer->createToken('next_auth_token')->plainTextToken;

            Log::info('User Resolved', ['user_id' => $customer->id, 'is_new' => $isNew]);

            return response()->json([
                'access_token' => $token,
                'user' => $this->serializeCustomer($customer),
                'is_new' => $isNew,
                'needs_phone' => empty($customer->phone_number),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Google OAuth Handshake Failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Authentication failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolve a Google sign-in to a single identity. Returns [OlCustomer, isNew].
     *
     * Order matters:
     *   1. Known Google account -> log that customer in.
     *   2. Email already belongs to a customer -> link Google, mark verified, and
     *      (security) null the password of a still-unverified account.
     *   3. Otherwise -> create a passwordless, already-verified identity.
     */
    protected function resolveGoogleLogin(string $googleId, string $email, ?string $name, ?string $avatar): array
    {
        // 1) Known Google account.
        $social = OlCustomerSocialAccount::where('provider', 'google')
            ->where('provider_user_id', $googleId)
            ->first();

        if ($social && $social->customer) {
            $customer = $social->customer;
            // Only fill blanks — never clobber a name/avatar the user edited locally.
            $customer->update($this->fillBlanks($customer, $name, $avatar));

            return [$customer, false];
        }

        // 2) Email already belongs to a customer -> link.
        $customer = OlCustomer::where('email', $email)->first();
        if ($customer) {
            $wasUnverified = is_null($customer->email_verified_at);

            $updates = $this->fillBlanks($customer, $name, $avatar);
            // Google proves ownership of this email address.
            $updates['email_verified_at'] = $customer->email_verified_at ?? now();

            // Security: an unverified email account may have been created by a
            // squatter. Null its password so only the real owner (who just proved
            // the email via Google) can re-set it through forgot/set-password.
            // This also affects an honest user who never verified — they simply
            // reset their password; the account keeps working via Google.
            if ($wasUnverified) {
                $updates['password'] = null;
            }

            $customer->update($updates);

            $customer->socialAccounts()->create([
                'provider' => 'google',
                'provider_user_id' => $googleId,
                'provider_email' => $email,
                'provider_avatar' => $avatar,
            ]);

            return [$customer, false];
        }

        // 3) Brand-new passwordless, verified identity.
        $customer = OlCustomer::create([
            'name' => $name ?: $email,
            'email' => $email,
            'password' => null,
            'phone_number' => null,
            'avatar_url' => $avatar,
            'email_verified_at' => now(),
        ]);

        $customer->socialAccounts()->create([
            'provider' => 'google',
            'provider_user_id' => $googleId,
            'provider_email' => $email,
            'provider_avatar' => $avatar,
        ]);

        return [$customer, true];
    }

    /** Build an update array that only fills empty name/avatar_url fields. */
    protected function fillBlanks(OlCustomer $customer, ?string $name, ?string $avatar): array
    {
        $updates = [];
        if (empty($customer->name) && ! empty($name)) {
            $updates['name'] = $name;
        }
        if (empty($customer->avatar_url) && ! empty($avatar)) {
            $updates['avatar_url'] = $avatar;
        }

        return $updates;
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $customer = OlCustomer::where('email', $request->email)->first();
        if ($customer) {
            $token = Str::random(64);
            DB::table('ol_customer_password_reset_tokens')->updateOrInsert(
                ['email' => $customer->email],
                ['token' => Hash::make($token), 'created_at' => now()],
            );

            $url = $this->frontendUrl('/reset-password', [
                'token' => $token,
                'email' => $customer->email,
            ]);

            Mail::to($customer->email)->send(new OlCustomerResetPasswordMail($customer->name, $url));
        }

        // Always identical (enumeration-safe): don't reveal whether the email exists.
        return response()->json([
            'message' => 'Jika email terdaftar, kami telah mengirim tautan untuk menyetel ulang password.',
        ]);
    }

    /**
     * Reset OR first-time set password (works whether the account previously had a
     * password or was Google-only).
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('ol_customer_password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record
            || ! Hash::check($request->token, $record->token)
            || Carbon::parse($record->created_at)->lt(now()->subMinutes(60))) {
            return response()->json([
                'message' => 'Tautan tidak valid atau telah kedaluwarsa.',
                'error_code' => 'invalid_token',
            ], 422);
        }

        $customer = OlCustomer::where('email', $request->email)->first();
        if (! $customer) {
            return response()->json(['message' => 'Akun tidak ditemukan.'], 404);
        }

        $customer->update(['password' => Hash::make($request->password)]);
        DB::table('ol_customer_password_reset_tokens')->where('email', $request->email)->delete();
        $customer->tokens()->delete(); // force re-login everywhere

        return response()->json(['message' => 'Password berhasil disetel. Silakan masuk.']);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
        ]);

        $record = DB::table('ol_customer_email_verification_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record
            || ! Hash::check($request->token, $record->token)
            || Carbon::parse($record->created_at)->lt(now()->subHours(24))) {
            return response()->json([
                'message' => 'Tautan verifikasi tidak valid atau telah kedaluwarsa.',
                'error_code' => 'invalid_token',
            ], 422);
        }

        $customer = OlCustomer::where('email', $request->email)->first();
        if ($customer && is_null($customer->email_verified_at)) {
            $customer->update(['email_verified_at' => now()]);
        }
        DB::table('ol_customer_email_verification_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Email berhasil diverifikasi.']);
    }

    public function resendVerification(Request $request)
    {
        $customer = $request->user();

        if ($customer->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email Anda sudah terverifikasi.']);
        }

        $this->sendVerificationEmail($customer);

        return response()->json(['message' => 'Email verifikasi telah dikirim ulang.']);
    }

    public function me(Request $request)
    {
        return response()->json($this->serializeCustomer($request->user()));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3|max:255',
            'phone_number' => 'nullable|string|max:20',
        ]);

        $request->user()->update([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
        ]);

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $this->serializeCustomer($request->user()->fresh()),
        ]);
    }

    public function updatePhone(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string|min:8|max:15',
        ]);

        $request->user()->update([
            'phone_number' => $validated['phone_number'],
        ]);

        return response()->json([
            'message' => 'Nomor telepon berhasil diperbarui.',
            'user' => $this->serializeCustomer($request->user()->fresh()),
        ]);
    }

    /**
     * Change password (requires current) OR set the first password for a
     * passwordless / Google-only account (no current password required).
     */
    public function updatePassword(Request $request)
    {
        $customer = $request->user();
        $hasPassword = $customer->hasPassword();

        $request->validate([
            'current_password' => ($hasPassword ? 'required' : 'nullable').'|string|min:6',
            'new_password' => 'required|string|min:8',
            'new_password_confirmation' => 'required|same:new_password',
        ]);

        if ($hasPassword && ! Hash::check($request->current_password, $customer->password)) {
            return response()->json([
                'message' => 'Password saat ini tidak sesuai.',
                'error_code' => 'wrong_current_password',
            ], 422);
        }

        $customer->update(['password' => Hash::make($request->new_password)]);

        return response()->json([
            'message' => $hasPassword ? 'Password berhasil diubah.' : 'Password berhasil disetel.',
        ]);
    }

    public function getLinkedAccounts(Request $request)
    {
        $customer = $request->user();

        return response()->json([
            'has_password' => $customer->hasPassword(),
            'providers' => $customer->socialAccounts()->pluck('provider')->values(),
        ]);
    }

    public function unlinkProvider(Request $request, string $provider)
    {
        $customer = $request->user();

        $social = $customer->socialAccounts()->where('provider', $provider)->first();
        if (! $social) {
            return response()->json(['message' => 'Metode login ini tidak tertaut.'], 404);
        }

        // Never leave an account with no way to log in.
        $otherProviders = $customer->socialAccounts()->where('provider', '!=', $provider)->count();
        if (! $customer->hasPassword() && $otherProviders === 0) {
            return response()->json([
                'message' => 'Tidak dapat melepas satu-satunya metode login. Setel password terlebih dahulu.',
                'error_code' => 'last_login_method',
            ], 422);
        }

        $social->delete();

        return response()->json(['message' => 'Metode login berhasil dilepas.']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil keluar',
        ]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    protected function sendVerificationEmail(OlCustomer $customer): void
    {
        $token = Str::random(64);
        DB::table('ol_customer_email_verification_tokens')->updateOrInsert(
            ['email' => $customer->email],
            ['token' => Hash::make($token), 'created_at' => now()],
        );

        $url = $this->frontendUrl('/verify-email', [
            'token' => $token,
            'email' => $customer->email,
        ]);

        Mail::to($customer->email)->send(new OlCustomerVerifyEmailMail($customer->name, $url));
    }

    /** Serialize a customer for API responses, exposing has_password. */
    protected function serializeCustomer(OlCustomer $customer): array
    {
        return array_merge($customer->toArray(), [
            'has_password' => $customer->hasPassword(),
        ]);
    }

    /** Build an absolute storefront URL with query params. */
    protected function frontendUrl(string $path, array $query = []): string
    {
        $base = rtrim(config('app.frontend_url'), '/');

        return $base.$path.($query ? '?'.http_build_query($query) : '');
    }
}
