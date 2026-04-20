<?php

use App\Features\Media\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MediaController::class, 'index']);
Route::post('/', [MediaController::class, 'store']);
Route::put('/{id}', [MediaController::class, 'update']);
Route::delete('/{id}', [MediaController::class, 'destroy']);
