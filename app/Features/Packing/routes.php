<?php

namespace App\Features\Packing;

use App\Features\Packing\Controllers\PackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PackingController::class, 'index']);
Route::post('/', [PackingController::class, 'store']);
Route::get('/summary', [PackingController::class, 'summary']);
Route::post('/bulk-summary', [PackingController::class, 'bulkSummary']);
Route::delete('/{id}', [PackingController::class, 'destroy']);
