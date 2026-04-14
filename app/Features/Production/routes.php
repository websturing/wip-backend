<?php

namespace App\Features\Production;

use Illuminate\Support\Facades\Route;
use App\Features\Production\Controllers\ProductionController;
use App\Features\Production\Controllers\ImportController;

Route::get('/', [ProductionController::class, 'index']);
Route::get('/dashboard', [ProductionController::class, 'dashboard']);
Route::get('/lines', [ProductionController::class, 'lines']);
Route::get('/summary', [ProductionController::class, 'summary']);
Route::get('/bulk-summary', [ProductionController::class, 'bulkSummary']);
Route::get('/{id}', [ProductionController::class, 'show']);
Route::post('/', [ProductionController::class, 'store']);
Route::put('/{id}', [ProductionController::class, 'update']);
Route::post('/import', [ImportController::class, 'import']);
Route::get('/latest-manpower', [ProductionController::class, 'latestManpower']);
Route::delete('/{id}', [ProductionController::class, 'destroy']);
