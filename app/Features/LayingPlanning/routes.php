<?php

use Illuminate\Support\Facades\Route;
use App\Features\LayingPlanning\Controllers\LayingPlanningController;
use App\Features\LayingPlanning\Controllers\LayingPlanningTypeController;
use App\Features\LayingPlanning\Controllers\LayingPlanningDetailController;
use App\Features\LayingPlanning\Controllers\LayingPlanningDetailTypeController;
use App\Features\LayingPlanning\Controllers\LayingPlanningPdfController;

// ===================== LAYING PLANNING TYPE =====================

Route::prefix('types')->group(function () {
    Route::get('/', [LayingPlanningTypeController::class, 'index'])->middleware('permission:laying_planning.read');
    Route::get('/{id}', [LayingPlanningTypeController::class, 'show'])->middleware('permission:laying_planning.read');
    Route::post('/', [LayingPlanningTypeController::class, 'store'])->middleware('permission:laying_planning.create');
    Route::put('/{id}', [LayingPlanningTypeController::class, 'update'])->middleware('permission:laying_planning.update');
    Route::delete('/{id}', [LayingPlanningTypeController::class, 'destroy'])->middleware('permission:laying_planning.delete');
});

// ===================== LAYING PLANNING DETAIL TYPE =====================
// Defined BEFORE LayingPlanning main routes to avoid /{id} catch-all conflict
// with the literal "detail-types" path.

Route::prefix('detail-types')->group(function () {
    Route::get('/', [LayingPlanningDetailTypeController::class, 'index'])->middleware('permission:laying_planning.read');
    Route::get('/{id}', [LayingPlanningDetailTypeController::class, 'show'])->middleware('permission:laying_planning.read');
    Route::post('/', [LayingPlanningDetailTypeController::class, 'store'])->middleware('permission:laying_planning.create');
    Route::put('/{id}', [LayingPlanningDetailTypeController::class, 'update'])->middleware('permission:laying_planning.update');
    Route::delete('/{id}', [LayingPlanningDetailTypeController::class, 'destroy'])->middleware('permission:laying_planning.delete');
});

// ===================== LAYING PLANNING =====================

Route::middleware('permission:laying_planning.read')->group(function() {
    Route::get('/', [LayingPlanningController::class, 'index']);
    Route::get('/{id}', [LayingPlanningController::class, 'show']);
    Route::get('/{id}/export-pdf', [LayingPlanningPdfController::class, 'exportPdf']);
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

// ===================== LAYING PLANNING DETAIL =====================

Route::middleware('permission:laying_planning.read')->group(function() {
    Route::get('/{lpId}/details', [LayingPlanningDetailController::class, 'index']);
    Route::get('/{lpId}/details/{detailId}', [LayingPlanningDetailController::class, 'show']);
});

Route::middleware('permission:laying_planning.create')->group(function() {
    Route::post('/{lpId}/details', [LayingPlanningDetailController::class, 'store']);
    Route::post('/{lpId}/details/{detailId}/duplicate', [LayingPlanningDetailController::class, 'duplicate']);
});

Route::middleware('permission:laying_planning.update')->group(function() {
    Route::put('/{lpId}/details/{detailId}', [LayingPlanningDetailController::class, 'update']);
});

Route::middleware('permission:laying_planning.delete')->group(function() {
    Route::delete('/{lpId}/details/{detailId}', [LayingPlanningDetailController::class, 'destroy']);
});
