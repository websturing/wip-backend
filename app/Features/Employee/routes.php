<?php

use Illuminate\Support\Facades\Route;
use App\Features\Employee\Controllers\EmployeeController;

Route::middleware('auth:sanctum')->group(function() {
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::get('/{id}', [EmployeeController::class, 'show']);
        Route::post('/', [EmployeeController::class, 'store']);
        Route::put('/{id}', [EmployeeController::class, 'update']);
        Route::delete('/{id}', [EmployeeController::class, 'destroy']);
    });
});
