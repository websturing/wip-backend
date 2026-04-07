<?php

use Illuminate\Support\Facades\Route;
use App\Features\Lines\Controllers\LineController;

Route::get('/', [LineController::class, 'index']);
Route::post('/', [LineController::class, 'store']);
Route::get('/{id}', [LineController::class, 'show']);
Route::put('/{id}', [LineController::class, 'update']);
Route::delete('/{id}', [LineController::class, 'destroy']);
