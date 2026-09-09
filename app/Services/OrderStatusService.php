<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Events\OrderStatusChanged;

class OrderStatusService
{
    public function transition(
        Order $order,
        User $changedBy,
        OrderStatus $expectedStatus,
        OrderStatus $nextStatus,
    ): Order {
        return DB::transaction(function () use (
            $order,
            $changedBy,
            $expectedStatus,
            $nextStatus,
        ): Order {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($lockedOrder->status !== $expectedStatus) {
                throw ValidationException::withMessages([
                    'order' => [
                        "This order must be {$expectedStatus->value} before it can be {$nextStatus->value}.",
                    ],
                ]);
            }

            $lockedOrder->update([
                'status' => $nextStatus,
            ]);

            $lockedOrder->statusHistory()->create([
                'from_status' => $expectedStatus,
                'to_status' => $nextStatus,
                'changed_by' => $changedBy->id,
            ]);

            OrderStatusChanged::dispatch(
                $lockedOrder,
                $expectedStatus,
                $nextStatus,
            );

            return $lockedOrder->load('store', 'items', 'payment');
        });
    }
}
