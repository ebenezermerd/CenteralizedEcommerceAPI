<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Public routes
Route::middleware('api')->group(function () {
    Route::post('/auth/sign-up', [AuthController::class, 'register']);
    Route::post('/auth/sign-in', [AuthController::class, 'login']);
});

Route::middleware(['jwt'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'getUser']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // User routes
    Route::get('users/list', [UserController::class, 'index']);
    Route::get('user/details/{id}', [UserController::class, 'show']);
    Route::post('users/create', [UserController::class, 'store']);
    
    // Product routes
    Route::post('products/create', [ProductController::class, 'store']);
    Route::get('products/list', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::get('product/details', [ProductController::class, 'show']);
    // Category routes
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/all', [CategoryController::class, 'all']);
        Route::get('/group/{group}', [CategoryController::class, 'subcategories']);
        Route::get('/name/{name}', [CategoryController::class, 'findByName']);
        Route::get('/{name}', [CategoryController::class, 'show']);
        Route::get('/product/{name}', [CategoryController::class, 'findProductCategory']);
    });
});
