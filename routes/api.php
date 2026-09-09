<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\StoreController;
use App\Http\Controllers\Api\V1\StoreProductController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\MerchantOrderController;
use App\Http\Controllers\Api\V1\DriverDeliveryController;
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

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    Route::get('/merchant/orders', [MerchantOrderController::class, 'index']);
    Route::get('/merchant/orders/{order}', [MerchantOrderController::class, 'show']);

    Route::post('/merchant/orders/{order}/accept', [
        MerchantOrderController::class,
        'accept',
    ]);

    Route::post('/merchant/orders/{order}/preparing', [
        MerchantOrderController::class,
        'markPreparing',
    ]);

    Route::post('/merchant/orders/{order}/ready', [
        MerchantOrderController::class,
        'markReady',
    ]);

    Route::get('/driver/orders/available', [
        DriverDeliveryController::class,
        'availableOrders',
    ]);

    Route::get('/driver/deliveries', [
        DriverDeliveryController::class,
        'index',
    ]);

    Route::post('/driver/orders/{order}/delivery', [
        DriverDeliveryController::class,
        'accept',
    ]);

    Route::post('/driver/deliveries/{delivery}/pickup', [
        DriverDeliveryController::class,
        'markPickedUp',
    ]);

    Route::post('/driver/deliveries/{delivery}/complete', [
        DriverDeliveryController::class,
        'complete',
    ]);
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

        Route::get('/cart', [CartController::class, 'show']);
        Route::post('/cart/items', [CartController::class, 'store']);
        Route::patch('/cart/items/{cartItem}', [CartController::class, 'update']);
        Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy']);

        Route::get('/addresses', [AddressController::class, 'index']);
        Route::post('/addresses', [AddressController::class, 'store']);
        Route::patch('/addresses/{address}', [AddressController::class, 'update']);
        Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);

        Route::post('/orders/{order}/cancel', [
            OrderController::class,
            'cancel',
        ]);

        Route::patch('/driver/availability', [
            DriverDeliveryController::class,
            'updateAvailability',
        ]);
    });
