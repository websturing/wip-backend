<?php

use Illuminate\Support\Facades\Route;
use App\Features\Wip\Controllers\WipController;

Route::get('/', [WipController::class, 'index']);
Route::post('/', [WipController::class, 'store']);
Route::get('/{id}', [WipController::class, 'show']);
Route::put('/{id}', [WipController::class, 'update']);
Route::delete('/{id}', [WipController::class, 'destroy']);
