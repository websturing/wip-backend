<?php

use App\Features\Productivity\Controllers\ProductivityController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:production.read')->group(function() {
    Route::get('/', [ProductivityController::class, 'index']);
    Route::get('/export-daily', [ProductivityController::class, 'exportByDate']);
    Route::get('/report-data', [ProductivityController::class, 'reportData']);
    Route::get('/last-info/{lotId}', [ProductivityController::class, 'lastInfo']);
    Route::get('/{id}', [ProductivityController::class, 'show']);
    Route::get('/{id}/export', [ProductivityController::class, 'export']);
});

Route::middleware('permission:production.create')->group(function() {
    Route::post('/', [ProductivityController::class, 'store']);
});

Route::middleware('permission:production.update')->group(function() {
    Route::put('/{id}', [ProductivityController::class, 'update']);
});

Route::middleware('permission:production.delete')->group(function() {
    Route::delete('/{id}', [ProductivityController::class, 'destroy']);
});
