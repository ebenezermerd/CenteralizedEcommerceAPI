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
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\AddressBookController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;

// Health Check
Route::get('/health', [HealthController::class, 'check']);
Route::get('/products/list', [ProductController::class, 'index']);
// Public routes
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/product/details', [ProductController::class, 'show']);

/**
 * @group Authentication
 *
 * APIs for managing authentication
 */
Route::post('/auth/sign-up', [AuthController::class, 'register']);
Route::post('/auth/sign-in', [AuthController::class, 'login']);
Route::post('/auth/refresh', [AuthController::class, 'refresh']);

// Password Reset Routes
Route::post('/auth/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::get('/auth/reset-password/{token}', [ResetPasswordController::class, 'checkToken']);
Route::post('/auth/reset-password', [ResetPasswordController::class, 'reset']);

// Email verification routes
Route::post('/auth/email/verify', [EmailVerificationController::class, 'verifyEmail']);
Route::post('/auth/email/verify/resend', [EmailVerificationController::class, 'resendVerificationEmail']);
Route::post('/auth/email/send-otp', [EmailVerificationController::class, 'sendVerificationOTP']);


Route::post('chapa/callback', [ChapaController::class, 'handleCallback'])->name('chapa.callback');
Route::post('chapa/webhook', [OrderController::class, 'handleWebhook'])->name('chapa.webhook');
Route::get('chapa/return', [ChapaController::class, 'handleReturn'])->name('chapa.return');

// Protected routes
Route::middleware(['jwt'])->group(function () {
    // Auth routes
    Route::get('/auth/me', [AuthController::class, 'getUser']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/update-password', [ResetPasswordController::class, 'updatePassword']);

    // MFA routes
    Route::post('/auth/mfa-enable', [MFAController::class, 'enable']);
    Route::post('/auth/mfa-disable', [MFAController::class, 'disable']);
    Route::get('/auth/mfa-status', [MFAController::class, 'getStatus']);
    Route::post('/auth/mfa-verify', [MFAController::class, 'verify']);
    Route::post('/auth/mfa-resend', [MFAController::class, 'resendOtp']);
    Route::post('/users/update/{id}', [UserController::class, 'update']);


    // Address routes
    Route::get('user/{userId}/addresses', [AddressBookController::class, 'index']);
    Route::post('user/{userId}/addresses/create', [AddressBookController::class, 'store']);
    Route::put('user/{userId}/addresses/{addressId}', [AddressBookController::class, 'update']);
    Route::delete('user/{userId}/addresses/{addressId}', [AddressBookController::class, 'destroy']);

    // Mail routes
    Route::prefix('mail')->group(function () {
        Route::get('list', [MailController::class, 'list']);
        Route::get('details', [MailController::class, 'details']);
        Route::get('labels', [MailController::class, 'labels']);
        Route::post('create', [MailController::class, 'create']);
        Route::post('mark-as-read/{mailId}', [MailController::class, 'markAsRead']);
        Route::post('toggle-star/{mailId}', [MailController::class, 'toggleStar']);
        Route::delete('delete/{mailId}', [MailController::class, 'delete']);
        Route::get('search', [MailController::class, 'search']);
        Route::post('draft', [MailController::class, 'saveDraft']);
        Route::put('draft/{id}', [MailController::class, 'updateDraft']);
    });

    Route::prefix('analytics')->group(function () {
        Route::get('widget-summary', [AnalyticsController::class, 'getWidgetSummary']);
        Route::get('current-visits', [AnalyticsController::class, 'getCurrentVisits']);
        Route::get('order-timeline', [AnalyticsController::class, 'getOrderTimeline']);
    });


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
        Route::post('products/{id}/transfer-vendor', [ProductController::class, 'transferVendor']);
    });

    // Admin and Supplier routes
    Route::middleware(['role:admin|supplier'])->group(function () {
        Route::post('products/create', [ProductController::class, 'store']);
        Route::post('products/update/{id}', [ProductController::class, 'update']);
        Route::put('products/publish/{id}', [ProductController::class, 'publishChange']);
    });

    // Review routes
    Route::prefix('reviews')->group(function () {
        Route::get('/', [ReviewController::class, 'index']);
        Route::post('/create', [ReviewController::class, 'store']);
        Route::put('/{id}', [ReviewController::class, 'update']);
        Route::delete('/{id}', [ReviewController::class, 'destroy']);
        Route::post('/{id}/helpful', [ReviewController::class, 'helpful']);
    });

    // Invoice routes
    Route::get('invoices/list', [InvoiceController::class, 'index']);
    Route::get('invoices/{id}', [InvoiceController::class, 'show']);
    Route::put('invoices/{id}', [InvoiceController::class, 'update']);
    Route::delete('invoices/{id}', [InvoiceController::class, 'destroy']);
    Route::get('users/{userId}/invoices', [InvoiceController::class, 'userInvoices']);

    // Order routes
    Route::get('orders/list', [OrderController::class, 'index']);
    Route::get('orders/{id}', [OrderController::class, 'show']);
    Route::put('orders/{id}', [OrderController::class, 'updateStatus']);
    Route::post('/checkout/order', [OrderController::class, 'checkout']);
    Route::post('/order/{orderId}/payment-proof', [OrderController::class, 'uploadPaymentProof']);
    Route::delete('orders/{id}', [OrderController::class, 'destroy']);

    // Cart routes
    Route::get('cart', [CartController::class, 'index']);
    Route::post('cart', [CartController::class, 'store']);
    Route::get('cart/{id}', [CartController::class, 'show']);
    Route::put('cart/{id}', [CartController::class, 'update']);
    Route::delete('cart/{id}', [CartController::class, 'destroy']);

    // Company routes with proper authentication
    Route::apiResource('companies', CompanyController::class);
    Route::get('companies/vendor/{id}', [CompanyController::class, 'vendorCompany']);
    Route::put('companies/{company}/status', [CompanyController::class, 'updateStatus']);

    // Customer specific routes
    Route::middleware(['role:customer|admin|supplier'])->group(function () {
        Route::post('/checkout/orders', [OrderController::class, 'checkout']);
        Route::get('/orders/my-orders', [OrderController::class, 'myOrders']);
        Route::get('/orders/my-orders/{id}', [OrderController::class, 'show']);
    });

    // Analytics routes
    Route::middleware(['role:admin'])->prefix('analytics')->group(function () {
        Route::get('widget-summary', [AnalyticsController::class, 'getWidgetSummary']);
        Route::get('current-visits', [AnalyticsController::class, 'getCurrentVisits']);
        Route::get('website-visits', [AnalyticsController::class, 'getWebsiteVisits']);
        Route::get('order-timeline', [AnalyticsController::class, 'getOrderTimeline']);
    });
});
