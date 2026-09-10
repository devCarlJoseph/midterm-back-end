<?php

namespace App\Listeners;

use App\Events\DeliveryAssigned;
use App\Notifications\DeliveryAssignedNotification;

class NotifyCustomerOfDeliveryAssignment
{
    public function handle(DeliveryAssigned $event): void
    {
        $event->delivery
            ->order
            ->user
            ->notify(new DeliveryAssignedNotification($event->delivery));
    }
}
