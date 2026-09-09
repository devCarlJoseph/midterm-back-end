<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;

class PricingService
{
    public function withSubtotal(Cart $cart): Cart
    {
        $subtotalInCentavos = $cart->items->sum(
            fn (CartItem $cartItem): int => $this->lineTotalInCentavos($cartItem)
        );

        foreach ($cart->items as $cartItem) {
            $cartItem->setAttribute(
                'line_total',
                number_format($this->lineTotalInCentavos($cartItem) / 100, 2, '.', ''),
            );
        }

        $cart->setAttribute(
            'subtotal',
            number_format($subtotalInCentavos / 100, 2, '.', ''),
        );

        return $cart;
    }

    public function lineTotalInCentavos(CartItem $cartItem): int
    {
        return (int) round(((float) $cartItem->product->price) * 100) * $cartItem->quantity;
    }
}
