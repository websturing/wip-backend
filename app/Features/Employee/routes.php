<?php

use Illuminate\Support\Facades\Route;
use App\Features\Employee\Controllers\EmployeeController;
use App\Features\Employee\Controllers\IdentityTypeController;

Route::prefix('identity-types')->group(function () {
    Route::get('/', [IdentityTypeController::class, 'index']);
    Route::get('/{id}', [IdentityTypeController::class, 'show']);
    Route::post('/', [IdentityTypeController::class, 'store']);
    Route::put('/{id}', [IdentityTypeController::class, 'update']);
    Route::delete('/{id}', [IdentityTypeController::class, 'destroy']);
});

Route::get('/', [EmployeeController::class, 'index']);
Route::get('/{id}', [EmployeeController::class, 'show']);
Route::post('/', [EmployeeController::class, 'store']);
Route::put('/{id}', [EmployeeController::class, 'update']);
Route::delete('/{id}', [EmployeeController::class, 'destroy']);
