<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\DeliveryFeeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Events\OrderPlaced;
use App\Models\DeliveryOption;

class PlaceOrder
{
    public function __construct(
        private DeliveryFeeService $deliveryFeeService,
    ) {}

    public function handle(
        User $user,
        int $addressId,
        int $deliveryOptionId,
        PaymentMethod $paymentMethod,
    ): Order {
        return DB::transaction(function () use ($user, $addressId, $paymentMethod, $deliveryOptionId,): Order {
            $address = $user->addresses()->findOrFail($addressId);

            $cart = $user->cart()->first();

            if ($cart === null || $cart->store_id === null || ! $cart->items()->exists()) {
                throw ValidationException::withMessages([
                    'cart' => ['Your cart is empty.'],
                ]);
            }

            $store = Store::query()->findOrFail($cart->store_id);

            $deliveryOption = DeliveryOption::query()
                ->whereKey($deliveryOptionId)
                ->whereBelongsTo($store)
                ->active()
                ->lockForUpdate()
                ->first();

            if ($deliveryOption === null) {
                throw ValidationException::withMessages([
                    'delivery_option_id' => ['The selected delivery option is unavailable for this store.'],
                ]);
            }

            $cartItems = $cart->items()
                ->orderBy('product_id')
                ->get();

            $products = Product::query()
                ->whereKey($cartItems->pluck('product_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lines = [];
            $subtotalInCentavos = 0;

            foreach ($cartItems as $cartItem) {
                $product = $products->get($cartItem->product_id);

                if (
                    $product === null
                    || ! $product->is_available
                    || $product->store_id !== $cart->store_id
                    || $product->stock_quantity < $cartItem->quantity
                ) {
                    throw ValidationException::withMessages([
                        'cart' => ['One or more products are unavailable or out of stock.'],
                    ]);
                }

                $unitPriceInCentavos = (int) round(((float) $product->price) * 100);
                $lineTotalInCentavos = $unitPriceInCentavos * $cartItem->quantity;

                $subtotalInCentavos += $lineTotalInCentavos;

                $lines[] = [
                    'product' => $product,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $this->money($unitPriceInCentavos),
                    'line_total' => $this->money($lineTotalInCentavos),
                ];
            }

            $deliveryFeeInCentavos = $this->deliveryFeeService->calculateInCentavos(
                $store,
                $address,
                $deliveryOption,
            );

            $totalInCentavos = $subtotalInCentavos + $deliveryFeeInCentavos;

            $order = Order::query()->create([
                'order_number' => 'DALI-' . Str::upper((string) Str::uuid()),
                'user_id' => $user->id,
                'store_id' => $store->id,
                'address_id' => $address->id,
                'delivery_address' => $this->addressSnapshot($address),
                'status' => OrderStatus::Pending,
                'payment_method' => $paymentMethod,
                'subtotal' => $this->money($subtotalInCentavos),
                'delivery_fee' => $this->money($deliveryFeeInCentavos),
                'total' => $this->money($totalInCentavos),
                'delivery_option_id' => $deliveryOption->id,
                'delivery_option_name' => $deliveryOption->name,
                'estimated_delivery_minutes' => $deliveryOption->estimated_delivery_minutes,
            ]);

            foreach ($lines as $line) {
                $product = $line['product'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit' => $product->unit,
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);

                $product->decrement('stock_quantity', $line['quantity']);
            }

            $order->payment()->create([
                'method' => $paymentMethod,
                'status' => PaymentStatus::Pending,
                'amount' => $order->total,
            ]);

            $order->statusHistory()->create([
                'from_status' => null,
                'to_status' => OrderStatus::Pending,
                'changed_by' => $user->id,
            ]);

            $cart->items()->delete();
            $cart->update(['store_id' => null]);

            OrderPlaced::dispatch($order);

            return $order->load('store', 'items', 'payment');
        });
    }

    private function money(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }

    /**
     * @return array<string, string|null>
     */
    private function addressSnapshot(Address $address): array
    {
        return [
            'label' => $address->label,
            'recipient_name' => $address->recipient_name,
            'phone' => $address->phone,
            'line_one' => $address->line_one,
            'line_two' => $address->line_two,
            'barangay' => $address->barangay,
            'city' => $address->city,
            'province' => $address->province,
            'postal_code' => $address->postal_code,
        ];
    }
}
