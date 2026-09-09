<?php

namespace App\Actions\Deliveries;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Events\DeliveryAssigned;

class AcceptDelivery
{
    public function handle(Order $order, User $driver): Delivery
    {
        return DB::transaction(function () use ($order, $driver): Delivery {
            $lockedDriver = User::query()
                ->lockForUpdate()
                ->findOrFail($driver->id);

            if (
                ! $lockedDriver->is_available_for_delivery
                || $lockedDriver->latitude === null
                || $lockedDriver->longitude === null
            ) {
                throw ValidationException::withMessages([
                    'driver' => ['You must be available and provide your current location.'],
                ]);
            }

            $hasActiveDelivery = Delivery::query()
                ->where('driver_id', $lockedDriver->id)
                ->whereIn('status', [
                    DeliveryStatus::Assigned,
                    DeliveryStatus::PickedUp,
                ])
                ->exists();

            if ($hasActiveDelivery) {
                throw ValidationException::withMessages([
                    'delivery' => ['You already have an active delivery.'],
                ]);
            }

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
                'driver_id' => $lockedDriver->id,
                'status' => DeliveryStatus::Assigned,
                'accepted_at' => now(),
            ]);

            DeliveryAssigned::dispatch($delivery);

            return $delivery->load('order.items', 'order.payment');
        });
    }
}
