<?php

use Illuminate\Support\Facades\Route;
use App\Features\User\Controllers\UserController;

Route::get('/', [UserController::class, 'index'])->middleware('permission:user.read');
