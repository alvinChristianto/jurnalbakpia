<?php

use App\Http\Controllers\DownloadPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::get('/bakpia-transaction-invoice/{record}', [DownloadPdfController::class, 'bakpiaTransaction'])->name('bakpiaTransaction.report');