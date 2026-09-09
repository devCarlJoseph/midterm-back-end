<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Cart\AddCartItem;
use App\Actions\Cart\RemoveCartItem;
use App\Actions\Cart\UpdateCartItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Services\PricingService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Request $request, PricingService $pricingService): CartResource
    {
        $cart = $request->user()
            ->cart()
            ->firstOrCreate()
            ->load('store', 'items.product.category');

        return new CartResource($pricingService->withSubtotal($cart));
    }

    public function store(
        AddCartItemRequest $request,
        AddCartItem $addCartItem,
        PricingService $pricingService,
    ): CartResource {
        $cart = $addCartItem->handle(
            $request->user(),
            $request->integer('product_id'),
            $request->integer('quantity'),
        );

        return new CartResource($pricingService->withSubtotal($cart));
    }

    public function update(
        UpdateCartItemRequest $request,
        CartItem $cartItem,
        UpdateCartItem $updateCartItem,
        PricingService $pricingService,
    ): CartResource {
        $cart = $updateCartItem->handle(
            $cartItem,
            $request->integer('quantity'),
        );

        return new CartResource($pricingService->withSubtotal($cart));
    }

    public function destroy(
        UpdateCartItemRequest $request,
        CartItem $cartItem,
        RemoveCartItem $removeCartItem,
        PricingService $pricingService,
    ): CartResource {
        $cart = $removeCartItem->handle($cartItem);

        return new CartResource($pricingService->withSubtotal($cart));
    }
}