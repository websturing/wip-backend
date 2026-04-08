<?php

use Illuminate\Support\Facades\Route;
use App\Features\Reference\Controllers\CustomerController;
use App\Features\Reference\Controllers\GlGroupController;
use App\Features\Reference\Controllers\LotController;
use App\Features\Reference\Controllers\ImportController;

Route::prefix('customers')->group(function () {
    Route::get('/', [CustomerController::class, 'index']);
    Route::post('/', [CustomerController::class, 'store']);
    Route::get('/{id}', [CustomerController::class, 'show']);
    Route::put('/{id}', [CustomerController::class, 'update']);
    Route::delete('/{id}', [CustomerController::class, 'destroy']);
});

Route::prefix('gl-groups')->group(function () {
    Route::get('/', [GlGroupController::class, 'index']);
    Route::post('/', [GlGroupController::class, 'store']);
    Route::get('/{id}', [GlGroupController::class, 'show']);
    Route::put('/{id}', [GlGroupController::class, 'update']);
    Route::delete('/{id}', [GlGroupController::class, 'destroy']);
});

Route::prefix('lots')->group(function () {
    Route::get('/', [LotController::class, 'index']);
    Route::get('/list', [LotController::class, 'list']);
    Route::post('/', [LotController::class, 'store']);
    Route::get('/{id}', [LotController::class, 'show']);
    Route::put('/{id}', [LotController::class, 'update']);
    Route::delete('/{id}', [LotController::class, 'destroy']);
});

Route::post('/import', [ImportController::class, 'upload']);
