<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\CartItem;

class RemoveCartItem
{
    public function handle(CartItem $cartItem): Cart
    {
        $cart = $cartItem->cart;

        $cartItem->delete();

        if (! $cart->items()->exists()) {
            $cart->update(['store_id' => null]);
        }

        return $cart->refresh()->load('store', 'items.product.category');
    }
}