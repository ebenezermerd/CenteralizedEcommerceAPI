<?php

use Illuminate\Support\Carbon;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MFAController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;

// Health Check
Route::get('/health', [HealthController::class, 'check']);

// Public routes
Route::post('/auth/sign-up', [AuthController::class, 'register']);
Route::post('/auth/sign-in', [AuthController::class, 'login']);
Route::post('/auth/refresh', [AuthController::class, 'refresh']);

// Protected routes
Route::middleware(['jwt'])->group(function () {
    // Auth routes
    Route::get('/auth/me', [AuthController::class, 'getUser']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // MFA routes
    Route::post('/auth/two-factor-challenge', [TwoFactorAuthenticationController::class, 'store']);
    Route::post('/auth/two-factor-authentication', [TwoFactorAuthenticationController::class, 'enable']);
    Route::delete('/auth/two-factor-authentication', [TwoFactorAuthenticationController::class, 'destroy']);
    Route::get('/auth/two-factor-qr-code', [TwoFactorAuthenticationController::class, 'show']);
    Route::get('/auth/two-factor-recovery-codes', [TwoFactorAuthenticatedSessionController::class, 'index']);
    Route::post('/auth/two-factor-recovery-codes', [TwoFactorAuthenticatedSessionController::class, 'store']);

    // Admin only routes
    Route::middleware(['role:admin'])->group(function () {
        // User management
        Route::get('users/list', [UserController::class, 'index']);
        Route::get('user/details/{id}', [UserController::class, 'show']);
        Route::post('users/create', [UserController::class, 'store']);
        Route::put('users/{id}', [UserController::class, 'update']);
        Route::delete('users/{id}', [UserController::class, 'destroy']);
        Route::put('users/{id}/role', [UserController::class, 'updateRole']);
        
        // Admin product management
        Route::delete('products/{id}', [ProductController::class, 'destroy']);
    });

    // Admin and Supplier routes
    Route::middleware(['role:admin|supplier'])->group(function () {
        Route::post('products/create', [ProductController::class, 'store']);
        Route::get('products/list', [ProductController::class, 'index']);
        Route::put('products/{id}', [ProductController::class, 'update']);
        Route::get('product/details', [ProductController::class, 'show']);
    });

    
    // Routes accessible by all authenticated users
    Route::get('products/list', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
});

// Customer specific routes
Route::middleware(['role:customer'])->group(function () {
    Route::post('reviews', [ReviewController::class, 'store']);
    Route::put('reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('reviews/{id}', [ReviewController::class, 'destroy']);
    
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/my-orders', [OrderController::class, 'myOrders']);
});
