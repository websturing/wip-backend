<?php

namespace App\Features\Lines;

use Illuminate\Support\Facades\Route;
use App\Features\Lines\Controllers\LineController;

Route::get('/', [LineController::class, 'index']);
Route::post('/', [LineController::class, 'store']);
Route::delete('/{id}', [LineController::class, 'destroy']);
