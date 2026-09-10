<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\OrderPlaced;
use App\Notifications\OrderPlacedNotification;

class NotifyMerchantOfNewOrder
{
    public function handle(OrderPlaced $event): void
    {
        $event->order
            ->store
            ->users()
            ->where('users.role', UserRole::Merchant->value)
            ->get()
            ->each
            ->notify(new OrderPlacedNotification($event->order));
    }
}
