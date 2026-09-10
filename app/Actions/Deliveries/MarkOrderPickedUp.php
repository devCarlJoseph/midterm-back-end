<?php

namespace App\Actions\Deliveries;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkOrderPickedUp
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
                $lockedDelivery->status !== DeliveryStatus::Assigned
                || $order->status !== OrderStatus::Ready
            ) {
                throw ValidationException::withMessages([
                    'delivery' => ['Only an assigned delivery with a ready order can be picked up.'],
                ]);
            }

            $lockedDelivery->update([
                'status' => DeliveryStatus::PickedUp,
                'picked_up_at' => now(),
            ]);

            $order->update([
                'status' => OrderStatus::PickedUp,
            ]);

            $order->statusHistory()->create([
                'from_status' => OrderStatus::Ready,
                'to_status' => OrderStatus::PickedUp,
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
