<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Notifications\OrderStatusChangedNotification;

class NotifyCustomerOfStatusChange
{
    public function handle(OrderStatusChanged $event): void
    {
        $event->order
            ->user
            ->notify(new OrderStatusChangedNotification(
                $event->order,
                $event->toStatus,
            ));
    }
}