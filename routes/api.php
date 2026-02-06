<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BakpiaController;
use App\Http\Controllers\Api\OrderController;
// Public products list
Route::get('/bakpias', [BakpiaController::class, 'index']);

// Protected checkout (Requires login)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/checkout', [OrderController::class, 'checkout']);
});
