<?php

namespace App\Actions\Deliveries;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptDelivery
{
    public function handle(Order $order, User $driver): Delivery
    {
        return DB::transaction(function () use ($order, $driver): Delivery {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($lockedOrder->status !== OrderStatus::Ready) {
                throw ValidationException::withMessages([
                    'order' => ['Only ready orders can be accepted for delivery.'],
                ]);
            }

            if ($lockedOrder->delivery()->exists()) {
                throw ValidationException::withMessages([
                    'order' => ['This order has already been accepted by another driver.'],
                ]);
            }

            $delivery = Delivery::query()->create([
                'order_id' => $lockedOrder->id,
                'driver_id' => $driver->id,
                'status' => DeliveryStatus::Assigned,
                'accepted_at' => now(),
            ]);

            return $delivery->load('order.items', 'order.payment');
        });
    }
}