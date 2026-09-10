<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderStatusService;

class MarkOrderPreparing
{
    public function __construct(
        private OrderStatusService $orderStatusService,
    ) {}

    public function handle(Order $order, User $merchant): Order
    {
        return $this->orderStatusService->transition(
            $order,
            $merchant,
            OrderStatus::Accepted,
            OrderStatus::Preparing,
        );
    }
}
