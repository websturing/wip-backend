<?php

use Illuminate\Support\Facades\Route;
use App\Features\IeLayout\Controllers\IeLayoutController;
use App\Features\IeLayout\Controllers\OperationController;
use App\Features\IeLayout\Controllers\IeLayoutManpowerController;

// 1. Specific Feature Routes (Prefixes)
Route::prefix('operations')->group(function () {
    Route::get('/', [OperationController::class, 'index']);
    Route::post('/', [OperationController::class, 'store']);
    Route::get('/{id}', [OperationController::class, 'show']);
    Route::put('/{id}', [OperationController::class, 'update']);
    Route::delete('/{id}', [OperationController::class, 'destroy']);
});

Route::prefix('manpower')->group(function () {
    Route::get('/', [IeLayoutManpowerController::class, 'index']);
    Route::post('/', [IeLayoutManpowerController::class, 'store']);
    Route::delete('/{id}', [IeLayoutManpowerController::class, 'destroy']);
});

// 2. Base Resource Routes (Generic IDs)
Route::get('/', [IeLayoutController::class, 'index']);
Route::post('/', [IeLayoutController::class, 'store']);
Route::get('/{id}', [IeLayoutController::class, 'show']);
Route::put('/{id}', [IeLayoutController::class, 'update']);
Route::delete('/{id}', [IeLayoutController::class, 'destroy']);
