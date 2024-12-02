
<?php

use App\Http\Controllers\AuthController;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Public routes
Route::middleware('api')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
});

Route::middleware(['jwt'])->group(function () {
    Route::get('/auth/user', [AuthController::class, 'getUser']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
