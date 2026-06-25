<?php

use Illuminate\Support\Facades\Route;
use App\Features\LayingPlanning\Controllers\LayingPlanningController;

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
