<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Validation\ValidationException;

class UpdateCartItem
{
    public function handle(CartItem $cartItem, int $quantity): Cart
    {
        $product = $cartItem->product;

        if (! $product->is_available || $quantity > $product->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['The requested quantity exceeds available stock.'],
            ]);
        }

        $cartItem->update(['quantity' => $quantity]);

        return $cartItem->cart->refresh()->load('store', 'items.product.category');
    }
}
