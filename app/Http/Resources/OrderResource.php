<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'payment_method' => $this->payment_method->value,
            'delivery_address' => $this->delivery_address,
            'subtotal' => $this->subtotal,
            'delivery_fee' => $this->delivery_fee,
            'delivery_option' => $this->when(
                $this->delivery_option_name !== null,
                fn() => [
                    'id' => $this->delivery_option_id,
                    'name' => $this->delivery_option_name->value,
                    'estimated_delivery_minutes' => $this->estimated_delivery_minutes,
                ],
            ),
            'total' => $this->total,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payment' => $this->whenLoaded('payment', fn() => [
                'method' => $this->payment->method->value,
                'status' => $this->payment->status->value,
                'amount' => $this->payment->amount,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'pickup_distance_kilometers' => $this->when(
                isset($this->pickup_distance_kilometers),
                $this->pickup_distance_kilometers,
            ),
        ];
    }
}
