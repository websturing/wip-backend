<?php

use Illuminate\Support\Facades\Route;
use App\Features\Reference\Controllers\CustomerController;
use App\Features\Reference\Controllers\GlGroupController;
use App\Features\Reference\Controllers\LotController;
use App\Features\Reference\Controllers\ImportController;
use App\Features\Reference\Controllers\GlSummaryController;
use App\Features\Reference\Controllers\ColorController;
use App\Features\Reference\Controllers\FabricController;

Route::middleware('permission:reference.read')->group(function() {
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index']);
        Route::get('/{id}', [CustomerController::class, 'show']);
    });
    
    Route::prefix('gl-groups')->group(function () {
        Route::get('/', [GlGroupController::class, 'index']);
        Route::get('/{id}/summary', [GlSummaryController::class, 'show']);
        Route::get('/{id}', [GlGroupController::class, 'show']);
    });
    
    Route::prefix('lots')->group(function () {
        Route::get('/', [LotController::class, 'index']);
        Route::get('/list', [LotController::class, 'list']);
        Route::get('/{id}', [LotController::class, 'show']);
    });
    
    Route::prefix('colors')->group(function () {
        Route::get('/', [ColorController::class, 'index']);
    });
    
    Route::prefix('fabrics')->group(function () {
        Route::get('/', [FabricController::class, 'index']);
    });
});

Route::middleware('permission:reference.create')->group(function() {
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::post('/gl-groups', [GlGroupController::class, 'store']);
    Route::post('/lots', [LotController::class, 'store']);
    Route::post('/import', [ImportController::class, 'upload']);
});

Route::middleware('permission:reference.update')->group(function() {
    Route::put('/customers/{id}', [CustomerController::class, 'update']);
    Route::put('/gl-groups/{id}', [GlGroupController::class, 'update']);
    Route::put('/lots/{id}', [LotController::class, 'update']);
});

Route::middleware('permission:reference.delete')->group(function() {
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);
    Route::delete('/gl-groups/{id}', [GlGroupController::class, 'destroy']);
    Route::delete('/lots/{id}', [LotController::class, 'destroy']);
});
