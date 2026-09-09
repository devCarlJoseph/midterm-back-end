<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DriverDeliveryController;
use App\Http\Controllers\Api\V1\MerchantOrderController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\StoreController;
use App\Http\Controllers\Api\V1\StoreProductController;
use Illuminate\Support\Facades\Route;

// Authentication
Route::prefix('v1/auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Public catalog
Route::prefix('v1')->group(function (): void {
    Route::controller(CategoryController::class)->group(function (): void {
        Route::get('/categories', 'index');
    });

    Route::controller(StoreController::class)
        ->prefix('stores')
        ->group(function (): void {
            Route::get('/', 'index');
            Route::get('/{store}', 'show');
        });

    Route::controller(StoreProductController::class)
        ->prefix('stores/{store}/products')
        ->group(function (): void {
            Route::get('/', 'index');
        });
});

// Authenticated API
Route::prefix('v1')
    ->middleware('auth:sanctum')
    ->scopeBindings()
    ->group(function (): void {
        // Authenticated account
        Route::prefix('auth')->controller(AuthController::class)->group(function (): void {
            Route::post('/logout', 'logout');
            Route::get('/me', 'me');
        });

        // Customer: cart and addresses
        Route::prefix('cart')->controller(CartController::class)->group(function (): void {
            Route::get('/', 'show');
            Route::post('/items', 'store');
            Route::patch('/items/{cartItem}', 'update');
            Route::delete('/items/{cartItem}', 'destroy');
        });

        Route::prefix('addresses')->controller(AddressController::class)->group(function (): void {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::patch('/{address}', 'update');
            Route::delete('/{address}', 'destroy');
        });

        // Customer: orders
        Route::prefix('orders')->controller(OrderController::class)->group(function (): void {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{order}', 'show');
            Route::post('/{order}/cancel', 'cancel');
        });

        // Merchant: store and inventory management
        Route::prefix('stores')->controller(StoreController::class)->group(function (): void {
            Route::post('/', 'store');
            Route::patch('/{store}', 'update');
            Route::delete('/{store}', 'destroy');
        });

        Route::prefix('stores/{store}/products')
            ->controller(StoreProductController::class)
            ->group(function (): void {
                Route::post('/', 'store');
                Route::patch('/{product}', 'update');
                Route::delete('/{product}', 'destroy');
            });

        // Merchant: order fulfillment
        Route::prefix('merchant/orders')
            ->controller(MerchantOrderController::class)
            ->group(function (): void {
                Route::get('/', 'index');
                Route::get('/{order}', 'show');
                Route::post('/{order}/accept', 'accept');
                Route::post('/{order}/preparing', 'markPreparing');
                Route::post('/{order}/ready', 'markReady');
            });

        // Driver: availability and deliveries
        Route::prefix('driver')->controller(DriverDeliveryController::class)->group(function (): void {
            Route::patch('/availability', 'updateAvailability');

            Route::get('/orders/available', 'availableOrders');
            Route::post('/orders/{order}/delivery', 'accept');

            Route::get('/deliveries', 'index');
            Route::post('/deliveries/{delivery}/pickup', 'markPickedUp');
            Route::post('/deliveries/{delivery}/complete', 'complete');
        });

        // User notifications
        Route::prefix('notifications')
            ->controller(NotificationController::class)
            ->group(function (): void {
                Route::get('/', 'index');
                Route::patch('/{notification}/read', 'markAsRead');
            });
    });