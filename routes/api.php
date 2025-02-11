<?php

use Illuminate\Support\Carbon;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MFAController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChapaController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;

// Health Check
Route::get('/health', [HealthController::class, 'check']);

// Public routes
Route::post('/auth/sign-up', [AuthController::class, 'register']);
Route::post('/auth/sign-in', [AuthController::class, 'login']);
Route::post('/auth/refresh', [AuthController::class, 'refresh']);

// Routes accessible by all authenticated users
Route::get('products/list', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show']);
Route::get('product/details', [ProductController::class, 'show']);

// Protected routes
Route::middleware(['jwt'])->group(function () {
    // Auth routes
    Route::get('/auth/me', [AuthController::class, 'getUser']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // MFA routes
    Route::post('/auth/mfa-enable', [AuthController::class, 'enableMfa']);
    Route::post('/auth/mfa-disable', [AuthController::class, 'disableMfa']);
    Route::get('/auth/mfa-status', [AuthController::class, 'getMfaStatus']);
    Route::post('/auth/mfa-verify', [AuthController::class, 'verifyMfa']);
    Route::post('/auth/mfa-resend', [AuthController::class, 'resendMfaOtp']);

    // Admin only routes
    Route::middleware(['role:admin'])->group(function () {
        // User management
        Route::get('users/list', [UserController::class, 'index']);
        Route::get('user/details/{id}', [UserController::class, 'show']);
        Route::post('users/create', [UserController::class, 'store']);
        Route::put('users/update/{id}', [UserController::class, 'update']);
        Route::delete('users/delete/{id}', [UserController::class, 'destroy']);
        Route::put('users/{id}/role', [UserController::class, 'updateRole']);

        // Admin product management
        Route::delete('products/{id}', [ProductController::class, 'destroy']);
    });

    // Admin and Supplier routes
    Route::middleware(['role:admin|supplier'])->group(function () {
        Route::post('products/update', [ProductController::class, 'store']);
        Route::post('products/create', [ProductController::class, 'store']);
        Route::get('products/list', [ProductController::class, 'index']);
        Route::get('products/details', [ProductController::class, 'show']);
        Route::put('users/update/{id}', [UserController::class, 'update']);
        Route::delete('products/delete/{id}', [ProductController::class, 'destroy']);
    });


    // Review routes
    Route::get('reviews', [ReviewController::class, 'index']);
    Route::get('reviews/{id}', [ReviewController::class, 'show']);


    // Invoice routes
    Route::get('invoices', [InvoiceController::class, 'index']);
    Route::get('invoices/{id}', [InvoiceController::class, 'show']);

    // Order routes
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{id}', [OrderController::class, 'show']);
    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::post('/order/{orderId}/payment-proof', [OrderController::class, 'uploadPaymentProof']);

    // Cart routes
    Route::get('cart', [CartController::class, 'index']);
    Route::post('cart', [CartController::class, 'store']);
    Route::get('cart/{id}', [CartController::class, 'show']);
    Route::put('cart/{id}', [CartController::class, 'update']);
    Route::delete('cart/{id}', [CartController::class, 'destroy']);
});

// Customer specific routes
Route::middleware(['role:customer|admin'])->group(function () {
    Route::post('reviews', [ReviewController::class, 'store']);
    Route::put('reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('reviews/{id}', [ReviewController::class, 'destroy']);

    Route::post('checkout/order', [OrderController::class, 'checkout']);
    Route::get('orders/my-orders', [OrderController::class, 'myOrders']);

});

Route::post('chapa/callback', [ChapaController::class, 'handleCallback'])->name('chapa.callback');
Route::post('chapa/webhook', [OrderController::class, 'handleWebhook'])->name('chapa.webhook');
Route::get('chapa/return', [ChapaController::class, 'handleReturn'])->name('chapa.return');
