<?php

namespace App\Actions\Deliveries;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteDelivery
{
    public function handle(Delivery $delivery, User $driver): Delivery
    {
        return DB::transaction(function () use ($delivery, $driver): Delivery {
            $lockedDelivery = Delivery::query()
                ->lockForUpdate()
                ->findOrFail($delivery->id);

            $order = $lockedDelivery->order()
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedDelivery->status !== DeliveryStatus::PickedUp
                || $order->status !== OrderStatus::PickedUp
            ) {
                throw ValidationException::withMessages([
                    'delivery' => ['Only a picked-up delivery can be completed.'],
                ]);
            }

            $lockedDelivery->update([
                'status' => DeliveryStatus::Delivered,
                'delivered_at' => now(),
            ]);

            $order->update([
                'status' => OrderStatus::Delivered,
            ]);

            $order->statusHistory()->create([
                'from_status' => OrderStatus::PickedUp,
                'to_status' => OrderStatus::Delivered,
                'changed_by' => $driver->id,
            ]);

            OrderStatusChanged::dispatch(
                $order,
                OrderStatus::Ready,
                OrderStatus::PickedUp,
            );

            return $lockedDelivery->refresh()->load('order.items', 'order.payment');
        });
    }
}
