<?php

use Illuminate\Support\Facades\Route;
use App\Features\productivity\Controllers\ProductivityController;

Route::get('/', [ProductivityController::class, 'index']);
Route::post('/', [ProductivityController::class, 'store']);
Route::get('/{id}', [ProductivityController::class, 'show']);
Route::put('/{id}', [ProductivityController::class, 'update']);
Route::delete('/{id}', [ProductivityController::class, 'destroy']);
