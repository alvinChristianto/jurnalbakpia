<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BakpiaController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AuthController;

// Public products list
Route::get('/bakpias', [BakpiaController::class, 'index']);
Route::get('/products', [BakpiaController::class, 'index']);

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Midtrans v1 token route without auth (for testing purposes)
Route::post('/midtranstokenv1', [OrderController::class, 'getTokenMidtransv1']);

// Midtrans callback route (to receive payment notifications)
Route::post('/midtrans-callback/', [OrderController::class, 'handleMidtransCallback']);

// get detail transaction by invoice number
Route::get('/transaction/{invoice_number}', [OrderController::class, 'getTransactionDetailByInvoice']);

// Protected checkout (Requires login)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);


    // Checkout route
    //Route::post('/midtranstokenv1', [OrderController::class, 'getTokenMidtransv1']);
});
