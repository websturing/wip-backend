<?php

use Illuminate\Support\Facades\Route;
use App\Features\IeLayout\Controllers\IeLayoutController;

Route::get('/', [IeLayoutController::class, 'index']);
Route::post('/', [IeLayoutController::class, 'store']);
Route::get('/{id}', [IeLayoutController::class, 'show']);
Route::put('/{id}', [IeLayoutController::class, 'update']);
Route::delete('/{id}', [IeLayoutController::class, 'destroy']);
