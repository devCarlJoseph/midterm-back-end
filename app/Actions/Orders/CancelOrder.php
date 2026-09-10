<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelOrder
{
    public function handle(Order $order, User $customer): Order
    {
        return DB::transaction(function () use ($order, $customer): Order {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($lockedOrder->user_id !== $customer->id) {
                abort(403);
            }

            if ($lockedOrder->status !== OrderStatus::Pending) {
                throw ValidationException::withMessages([
                    'order' => ['Only pending orders can be cancelled.'],
                ]);
            }

            $items = $lockedOrder->items()
                ->whereNotNull('product_id')
                ->orderBy('product_id')
                ->get();

            $products = Product::query()
                ->whereKey($items->pluck('product_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $product = $products->get($item->product_id);

                if ($product !== null) {
                    $product->increment('stock_quantity', $item->quantity);
                }
            }

            $lockedOrder->update([
                'status' => OrderStatus::Cancelled,
            ]);

            $lockedOrder->statusHistory()->create([
                'from_status' => OrderStatus::Pending,
                'to_status' => OrderStatus::Cancelled,
                'changed_by' => $customer->id,
            ]);

            OrderStatusChanged::dispatch(
                $lockedOrder,
                OrderStatus::Pending,
                OrderStatus::Cancelled,
            );

            return $lockedOrder->load('items', 'payment', 'store');
        });
    }
}
