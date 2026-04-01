<?php

use Illuminate\Support\Facades\Route;
use App\Features\Acl\Controllers\RoleController;

Route::get('/roles', [RoleController::class, 'index']);
Route::post('/roles', [RoleController::class, 'store']);
Route::get('/permissions', [RoleController::class, 'permissions']);
Route::post('/roles/{id}/permissions', [RoleController::class, 'updatePermissions']);
