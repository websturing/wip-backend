<?php

use Illuminate\Support\Facades\Route;
use App\Features\Acl\Controllers\RoleController;
use App\Features\Acl\Controllers\UserController;
use App\Features\Acl\Controllers\MenuController;
use App\Features\Acl\Controllers\PermissionController;

Route::get('/menus', [MenuController::class, 'index']); // Public/authorized menus for sidebar

Route::middleware('permission:acl.read')->group(function() {
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/permissions', [RoleController::class, 'permissions']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/menus/all', [MenuController::class, 'all']); // All menus for management
});

Route::middleware('permission:acl.update')->group(function() {
    // Permission CRUD
    Route::post('/permissions', [PermissionController::class, 'store']);
    Route::put('/permissions/{id}', [PermissionController::class, 'update']);
    Route::delete('/permissions/{id}', [PermissionController::class, 'destroy']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);
    Route::post('/roles/{id}/permissions', [RoleController::class, 'updatePermissions']);
    Route::post('/roles/{id}/menus', [RoleController::class, 'syncMenus']); // Sync menus to role

    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::post('/sync', [RoleController::class, 'sync']);

    // Menu CRUD
    Route::post('/menus', [MenuController::class, 'store']);
    Route::put('/menus/{id}', [MenuController::class, 'update']);
    Route::delete('/menus/{id}', [MenuController::class, 'destroy']);
});
