<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Cart\AddCartItem;
use App\Actions\Cart\RemoveCartItem;
use App\Actions\Cart\UpdateCartItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends Controller
{
    public function show(Request $request, PricingService $pricingService): JsonResponse
    {
        $cart = $request->user()
            ->cart()
            ->with('store', 'items.product.category')
            ->first();

        if ($cart === null) {
            $cart = new Cart;
            $cart->setRelation('store', null);
            $cart->setRelation('items', collect());
        }

        return (new CartResource($pricingService->withSubtotal($cart)))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function clear(Request $request, PricingService $pricingService): JsonResponse
    {
        $cart = $request->user()->cart()->first();

        if ($cart !== null) {
            $cart->items()->delete();
            $cart->update(['store_id' => null]);
            $cart->refresh()->load('store', 'items.product.category');
        } else {
            $cart = new Cart;
            $cart->setRelation('store', null);
            $cart->setRelation('items', collect());
        }

        return (new CartResource($pricingService->withSubtotal($cart)))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function store(
        AddCartItemRequest $request,
        AddCartItem $addCartItem,
        PricingService $pricingService,
    ): JsonResponse {
        $cart = $addCartItem->handle(
            $request->user(),
            $request->integer('product_id'),
            $request->integer('quantity'),
        );

        return (new CartResource($pricingService->withSubtotal($cart)))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateCartItemRequest $request,
        CartItem $cartItem,
        UpdateCartItem $updateCartItem,
        PricingService $pricingService,
    ): JsonResponse {
        $cart = $updateCartItem->handle(
            $cartItem,
            $request->integer('quantity'),
        );

        return (new CartResource($pricingService->withSubtotal($cart)))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function destroy(
        UpdateCartItemRequest $request,
        CartItem $cartItem,
        RemoveCartItem $removeCartItem,
        PricingService $pricingService,
    ): JsonResponse {
        $cart = $removeCartItem->handle($cartItem);

        return (new CartResource($pricingService->withSubtotal($cart)))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function updateByProduct(
        Request $request,
        Product $product,
        UpdateCartItem $updateCartItem,
        RemoveCartItem $removeCartItem,
        PricingService $pricingService,
    ): JsonResponse {
        $quantity = $request->integer('quantity');
        $cart = $request->user()->cart()->first();

        if ($cart === null) {
            $cart = new Cart;
            $cart->setRelation('store', null);
            $cart->setRelation('items', collect());
            return (new CartResource($pricingService->withSubtotal($cart)))
                ->response()
                ->setStatusCode(Response::HTTP_OK);
        }

        $cartItem = $cart->items()->where('product_id', $product->id)->first();

        if ($cartItem !== null) {
            if ($quantity <= 0) {
                $cart = $removeCartItem->handle($cartItem);
            } else {
                $cart = $updateCartItem->handle($cartItem, $quantity);
            }
        } else {
            $cart->load('store', 'items.product.category');
        }

        return (new CartResource($pricingService->withSubtotal($cart)))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function destroyByProduct(
        Request $request,
        Product $product,
        RemoveCartItem $removeCartItem,
        PricingService $pricingService,
    ): JsonResponse {
        $cart = $request->user()->cart()->first();

        if ($cart !== null) {
            $cartItem = $cart->items()->where('product_id', $product->id)->first();
            if ($cartItem !== null) {
                $cart = $removeCartItem->handle($cartItem);
            } else {
                $cart->load('store', 'items.product.category');
            }
        } else {
            $cart = new Cart;
            $cart->setRelation('store', null);
            $cart->setRelation('items', collect());
        }

        return (new CartResource($pricingService->withSubtotal($cart)))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
}
