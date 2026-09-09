<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddCartItem
{
    public function handle(User $user, int $productId, int $quantity): Cart
    {
        return DB::transaction(function () use ($user, $productId, $quantity): Cart {
            $product = Product::query()->findOrFail($productId);

            if (! $product->is_available || $product->stock_quantity < $quantity) {
                throw ValidationException::withMessages([
                    'product_id' => ['This product is unavailable or does not have enough stock.'],
                ]);
            }

            $cart = $user->cart()->firstOrCreate();

            if ($cart->store_id !== null && $cart->store_id !== $product->store_id) {
                throw ValidationException::withMessages([
                    'product_id' => ['You can only add products from one store at a time.'],
                ]);
            }

            $cart->update(['store_id' => $product->store_id]);

            $cartItem = $cart->items()->firstOrNew([
                'product_id' => $product->id,
            ]);

            $newQuantity = ($cartItem->exists ? $cartItem->quantity : 0) + $quantity;

            if ($newQuantity > $product->stock_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['The requested quantity exceeds available stock.'],
                ]);
            }

            $cartItem->quantity = $newQuantity;
            $cartItem->save();

            return $cart->refresh()->load('store', 'items.product.category');
        });
    }
}
