<?php

use Illuminate\Support\Facades\Route;
use App\Features\Wip\Controllers\WipController;
use App\Features\Wip\Controllers\WipReportController;

Route::get('/pending-services', [\App\Features\Wip\Controllers\PendingWipController::class, 'index']);
Route::get('/summary', [WipReportController::class, 'summary']);
Route::get('/balance-summary', [WipReportController::class, 'balanceSummary']);
Route::get('/get-colors', [WipReportController::class, 'getLotColors']);
Route::post('/export', [WipReportController::class, 'storeExport']);
Route::get('/', [WipController::class, 'index']);
Route::post('/', [WipController::class, 'store']);
Route::get('/{id}', [WipController::class, 'show']);
Route::put('/{id}', [WipController::class, 'update']);
Route::delete('/{id}', [WipController::class, 'destroy']);
