<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BakpiaController;
use App\Http\Controllers\Api\KiriminajaWebhookController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

// Public products list
Route::get('/bakpias', [BakpiaController::class, 'index']);
Route::get('/products', [BakpiaController::class, 'index']);

// Public outlets list (for checkout pickup selector)
Route::get('/outlets', [BakpiaController::class, 'outlets']);

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Public password reset / set-password + email verification
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);

// Midtrans v1 token route without auth (for testing purposes)
Route::post('/midtranstokenv1', [OrderController::class, 'getTokenMidtransv1']);

// Midtrans callback route (to receive payment notifications)
Route::post('/midtrans-callback/', [OrderController::class, 'handleMidtransCallback']);

// KiriminAja delivery status webhook (processed/shipped/canceled/finished/returned)
Route::post('/kiriminaja-callback/', [KiriminajaWebhookController::class, 'handle']);

// get detail transaction by invoice number
Route::get('/transaction/{invoice_number}', [OrderController::class, 'getTransactionDetailByInvoice']);

// get printable shipping label PDF (paid delivery orders only)
Route::get('/transaction/{invoice_number}/label', [OrderController::class, 'getShippingLabel']);

// get shipping tracking by invoice number (proxies to KiriminAja)
Route::get('/tracking/{invoice_number}', [OrderController::class, 'getShippingTracking']);

// get express shipping rates (price quote) — proxies to KiriminAja
Route::post('/shipping/pricing', [OrderController::class, 'getShippingPrice']);

// Protected checkout (Requires login)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/profile/phone', [AuthController::class, 'updatePhone']);
    Route::put('/profile/password', [AuthController::class, 'updatePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Email verification resend + linked login methods ("Akun Tertaut")
    Route::post('/email/resend-verification', [AuthController::class, 'resendVerification']);
    Route::get('/profile/linked-accounts', [AuthController::class, 'getLinkedAccounts']);
    Route::delete('/profile/linked-accounts/{provider}', [AuthController::class, 'unlinkProvider']);

    Route::get('/orderlists', [OrderController::class, 'orderlists']);

    // Checkout route
    // Route::post('/midtranstokenv1', [OrderController::class, 'getTokenMidtransv1']);
});
