<?php

namespace App\Notifications;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DeliveryAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Delivery $delivery,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function viaQueues(): array
    {
        return [
            'database' => 'notifications',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Driver assigned',
            'message' => "A driver has accepted order {$this->delivery->order->order_number}.",
            'order_id' => $this->delivery->order_id,
            'delivery_id' => $this->delivery->id,
        ];
    }
}