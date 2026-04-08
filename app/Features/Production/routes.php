<?php

namespace App\Features\Production;

use Illuminate\Support\Facades\Route;
use App\Features\Production\Controllers\ProductionController;
use App\Features\Production\Controllers\ImportController;

Route::get('/', [ProductionController::class, 'index']);
Route::get('/lines', [ProductionController::class, 'lines']);
Route::get('/summary', [ProductionController::class, 'summary']);
Route::post('/', [ProductionController::class, 'store']);
Route::post('/import', [ImportController::class, 'import']);
Route::delete('/{id}', [ProductionController::class, 'destroy']);
