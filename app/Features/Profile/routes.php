<?php

use Illuminate\Support\Facades\Route;
use App\Features\Profile\Controllers\ProfileController;

Route::get('/', [ProfileController::class, 'show']);
Route::put('/', [ProfileController::class, 'update']);
Route::put('/password', [ProfileController::class, 'changePassword']);
Route::patch('/preferences', [ProfileController::class, 'updatePreferences']);
