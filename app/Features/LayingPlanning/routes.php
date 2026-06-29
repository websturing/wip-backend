<?php

use Illuminate\Support\Facades\Route;
use App\Features\LayingPlanning\Controllers\LayingPlanningController;
use App\Features\LayingPlanning\Controllers\LayingPlanningTypeController;

Route::prefix('types')->group(function () {
    Route::get('/', [LayingPlanningTypeController::class, 'index'])->middleware('permission:laying_planning.read');
    Route::get('/{id}', [LayingPlanningTypeController::class, 'show'])->middleware('permission:laying_planning.read');
    Route::post('/', [LayingPlanningTypeController::class, 'store'])->middleware('permission:laying_planning.create');
    Route::put('/{id}', [LayingPlanningTypeController::class, 'update'])->middleware('permission:laying_planning.update');
    Route::delete('/{id}', [LayingPlanningTypeController::class, 'destroy'])->middleware('permission:laying_planning.delete');
});

Route::middleware('permission:laying_planning.read')->group(function() {
    Route::get('/', [LayingPlanningController::class, 'index']);
    Route::get('/{id}', [LayingPlanningController::class, 'show']);
});

Route::middleware('permission:laying_planning.create')->group(function() {
    Route::post('/', [LayingPlanningController::class, 'store']);
});

Route::middleware('permission:laying_planning.update')->group(function() {
    Route::put('/', [LayingPlanningController::class, 'update']);
});

Route::middleware('permission:laying_planning.delete')->group(function() {
    Route::delete('/{id}', [LayingPlanningController::class, 'destroy']);
});
