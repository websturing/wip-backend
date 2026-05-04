<?php

use Illuminate\Support\Facades\Route;
use App\Features\IeLayout\Controllers\IeLayoutController;
use App\Features\IeLayout\Controllers\OperationController;
use App\Features\IeLayout\Controllers\IeLayoutManpowerController;

Route::middleware('permission:ie_layout.read')->group(function() {
    Route::get('/', [IeLayoutController::class, 'index']);
    Route::prefix('operations')->group(function () {
        Route::get('/', [OperationController::class, 'index']);
        Route::get('/{id}', [OperationController::class, 'show']);
    });
    
    Route::prefix('manpower')->group(function () {
        Route::get('/', [IeLayoutManpowerController::class, 'index']);
    });

    Route::get('/{id}', [IeLayoutController::class, 'show']);
});

Route::middleware('permission:ie_layout.create')->group(function() {
    Route::post('/', [IeLayoutController::class, 'store']);
    Route::post('/operations', [OperationController::class, 'store']);
    Route::post('/manpower', [IeLayoutManpowerController::class, 'store']);
});

Route::middleware('permission:ie_layout.update')->group(function() {
    Route::put('/operations/{id}', [OperationController::class, 'update']);
    Route::put('/{id}', [IeLayoutController::class, 'update']);
});

Route::middleware('permission:ie_layout.delete')->group(function() {
    Route::delete('/operations/{id}', [OperationController::class, 'destroy']);
    Route::delete('/manpower/{id}', [IeLayoutManpowerController::class, 'destroy']);
    Route::delete('/{id}', [IeLayoutController::class, 'destroy']);
});
