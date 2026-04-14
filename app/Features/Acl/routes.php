<?php

use Illuminate\Support\Facades\Route;
use App\Features\Acl\Controllers\RoleController;
use App\Features\Acl\Controllers\UserController;

Route::middleware('permission:acl.read')->group(function() {
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/permissions', [RoleController::class, 'permissions']);
    Route::get('/users', [UserController::class, 'index']);
});

Route::middleware('permission:acl.update')->group(function() {
    Route::post('/roles', [RoleController::class, 'store']);
    Route::post('/roles/{id}/permissions', [RoleController::class, 'updatePermissions']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::post('/sync', [RoleController::class, 'sync']);
});
