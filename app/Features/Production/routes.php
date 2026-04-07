<?php

use Illuminate\Support\Facades\Route;
use App\Features\Production\Controllers\ProductionController;

Route::get('/lines', [ProductionController::class, 'getLines']);
Route::get('/', [ProductionController::class, 'index']);
Route::post('/', [ProductionController::class, 'store']);
Route::get('/{id}', [ProductionController::class, 'show']);
Route::put('/{id}', [ProductionController::class, 'update']);
Route::delete('/{id}', [ProductionController::class, 'destroy']);
