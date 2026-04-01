<?php

use Illuminate\Support\Facades\Route;
use App\Features\Acl\Controllers\RoleController;

Route::middleware('permission:acl.read')->group(function() {
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/permissions', [RoleController::class, 'permissions']);
});

Route::middleware('permission:acl.update')->group(function() {
    Route::post('/roles', [RoleController::class, 'store']);
    Route::post('/roles/{id}/permissions', [RoleController::class, 'updatePermissions']);
});
