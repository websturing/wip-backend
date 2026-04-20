<?php

use App\Features\Productivity\Controllers\ProductivityController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductivityController::class, 'index']);
Route::get('/last-info/{lotId}', [ProductivityController::class, 'lastInfo']);
Route::get('/{id}', [ProductivityController::class, 'show']);
Route::post('/', [ProductivityController::class, 'store']);
Route::put('/{id}', [ProductivityController::class, 'update']);
Route::get('/{id}/export', [ProductivityController::class, 'export']);
Route::delete('/{id}', [ProductivityController::class, 'destroy']);
