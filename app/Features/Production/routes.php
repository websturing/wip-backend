<?php

namespace App\Features\Production;

use Illuminate\Support\Facades\Route;
use App\Features\Production\Controllers\ProductionController;

Route::get('/', [ProductionController::class, 'index']);
Route::get('/lines', [ProductionController::class, 'lines']);
Route::get('/summary', [ProductionController::class, 'summary']);
Route::post('/', [ProductionController::class, 'store']);
Route::delete('/{id}', [ProductionController::class, 'destroy']);
