<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\StoreController;
use App\Http\Controllers\Api\V1\StoreProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::prefix('v1')->group(function (): void {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/stores', [StoreController::class, 'index']);
    Route::get('/stores/{store}', [StoreController::class, 'show']);
    Route::get('/stores/{store}/products', [StoreProductController::class, 'index']);
});

Route::prefix('v1')
    ->middleware('auth:sanctum')
    ->scopeBindings()
    ->group(function (): void {
        Route::post('/stores', [StoreController::class, 'store']);
        Route::patch('/stores/{store}', [StoreController::class, 'update']);
        Route::delete('/stores/{store}', [StoreController::class, 'destroy']);

        Route::post('/stores/{store}/products', [StoreProductController::class, 'store']);
        Route::patch('/stores/{store}/products/{product}', [StoreProductController::class, 'update']);
        Route::delete('/stores/{store}/products/{product}', [StoreProductController::class, 'destroy']);
    });
