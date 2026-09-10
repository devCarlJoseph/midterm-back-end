<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name->value,
            'description' => $this->description,
            'additional_fee' => $this->additional_fee,
            'estimated_delivery_minutes' => $this->estimated_delivery_minutes,
            'is_active' => $this->is_active,
        ];
    }
}
