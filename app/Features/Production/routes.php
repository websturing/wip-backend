<?php

namespace App\Features\Production;

use Illuminate\Support\Facades\Route;
use App\Features\Production\Controllers\ProductionController;
use App\Features\Production\Controllers\ImportController;

Route::get('/', [ProductionController::class, 'index'])->middleware('permission:production.read');
Route::get('/dashboard', [ProductionController::class, 'dashboard'])->middleware('permission:production.read');
Route::get('/lines', [ProductionController::class, 'lines']); // Public or semi-public
Route::get('/summary', [ProductionController::class, 'summary']);
Route::get('/bulk-summary', [ProductionController::class, 'bulkSummary']);
Route::get('/{id}', [ProductionController::class, 'show'])->middleware('permission:production.read');
Route::post('/', [ProductionController::class, 'store'])->middleware('permission:production.create');
Route::put('/{id}', [ProductionController::class, 'update'])->middleware('permission:production.update');
Route::post('/import', [ImportController::class, 'import'])->middleware('permission:production.create');
Route::get('/latest-manpower', [ProductionController::class, 'latestManpower']);
Route::delete('/{id}', [ProductionController::class, 'destroy'])->middleware('permission:production.delete');
