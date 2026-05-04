<?php

use Illuminate\Support\Facades\Route;
use App\Features\Auth\Controllers\AuthController;

Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::get('/', [AuthController::class, 'index']);
Route::post('/login', [AuthController::class, 'login'])->withoutMiddleware(['auth:sanctum']);
