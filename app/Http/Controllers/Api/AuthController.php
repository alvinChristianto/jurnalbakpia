<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OlCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException; // Import Facade Log

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $existing = OlCustomer::where('email', $request->email)->first();
        if ($existing && $existing->google_id) {
            return response()->json([
                'message' => 'Email ini sudah terdaftar melalui Google. Silakan masuk menggunakan tombol "Masuk dengan Google".',
                'error_code' => 'google_account_exists',
            ], 422);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:ol_customers',
            'password' => 'required|string|min:8',
            'phone_number' => 'nullable|string|max:20',
        ]);

        $customer = OlCustomer::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
        ]);

        $token = $customer->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $customer,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $customer = OlCustomer::where('email', $request->email)->first();

        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan salah.'],
            ]);
        }

        $token = $customer->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $customer,
        ]);
    }

    public function handleGoogleCallback(Request $request)
    {
        // 1. Log setiap request yang masuk
        Log::info('Google OAuth Handshake Started', [
            'payload' => $request->all(),
            'ip' => $request->ip(),
        ]);
        try {
            $request->validate([
                'email' => 'required|email',
                'google_id' => 'required',
            ]);

            Log::info('trying to create/update OlCustomer', [
                'payload' => $request->all(),
            ]);

            // Cek dulu apakah user sudah ada
            // ... di dalam fungsi callback
            $customer = OlCustomer::where('email', $request->email)->first();

            if ($customer) {
                $customer->update([
                    'google_id' => $request->google_id,
                    'name' => $request->name,
                ]);
            } else {
                $customer = OlCustomer::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'google_id' => $request->google_id,
                    'password' => Hash::make(Str::random(24)),
                ]);
            }

            Log::info('User Resolved', ['user_id' => $customer->id]);

            // 4. Generate Token Sanctum
            $token = $customer->createToken('next_auth_token')->plainTextToken;

            Log::info('Sanctum Token Generated Successfully', ['user_id' => $customer->id]);

            return response()->json([
                'access_token' => $token,
                'user' => $customer,
            ]);
        } catch (\Exception $e) {
            // 5. Log jika terjadi error (sangat penting!)
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

    public function me(Request $request)
    {
        return response()->json($request->user());
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
            'user' => $request->user()->fresh(),
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
            'user' => $request->user()->fresh(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string|min:6',
            'new_password' => 'required|string|min:8',
            'new_password_confirmation' => 'required|same:new_password',
        ]);

        $customer = $request->user();

        if (! Hash::check($request->current_password, $customer->password)) {
            return response()->json([
                'message' => 'Password saat ini tidak sesuai.',
                'error_code' => 'wrong_current_password',
            ], 422);
        }

        $customer->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Password berhasil diubah.']);
    }

    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil keluar',
        ]);
    }
}
