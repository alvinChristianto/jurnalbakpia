<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OlCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log; // Import Facade Log


class AuthController extends Controller
{
    public function register(Request $request)
    {
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
            'user' => $customer
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $customer = OlCustomer::where('email', $request->email)->first();

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan salah.'],
            ]);
        }

        $token = $customer->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $customer
        ]);
    }

    public function handleGoogleCallback(Request $request)
    {
        // 1. Log setiap request yang masuk
        Log::info('Google OAuth Handshake Started', [
            'payload' => $request->all(),
            'ip' => $request->ip()
        ]);
        try {
            $request->validate([
                'email' => 'required|email',
                'google_id' => 'required',
            ]);

            Log::info('trying to create/update OlCustomer', [
                'payload' => $request->all()
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
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Authentication failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil keluar'
        ]);
    }
}
