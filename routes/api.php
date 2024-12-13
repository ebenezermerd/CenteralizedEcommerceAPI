
<?php

use App\Http\Controllers\AuthController;
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
});
