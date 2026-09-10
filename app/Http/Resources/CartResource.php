<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store' => new StoreResource($this->whenLoaded('store')),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'subtotal' => $this->subtotal ?? '0.00',
        ];
    }
}
