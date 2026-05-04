<?php

use Illuminate\Support\Facades\Route;
use App\Features\Auth\Controllers\AuthController;

Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::get('/', [AuthController::class, 'index']);
Route::post('/login', [AuthController::class, 'login'])->withoutMiddleware(['auth:sanctum']);

// Auto-fix DB columns if missing
try {
    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'last_login_at')) {
        \Illuminate\Support\Facades\Schema::table('users', function ($table) {
            $table->timestamp('last_login_at')->nullable()->after('password');
        });
    }
    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'status')) {
        \Illuminate\Support\Facades\Schema::table('users', function ($table) {
            $table->string('status')->default('active')->after('last_login_at');
        });
    }
} catch (\Exception $e) {}
