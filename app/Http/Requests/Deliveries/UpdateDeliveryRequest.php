<?php

namespace App\Http\Requests\Deliveries;

use App\Models\Delivery;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $delivery = $this->route('delivery');

        return $delivery instanceof Delivery
            && ($this->user()?->can('update', $delivery) ?? false);
    }

    public function rules(): array
    {
        return [];
    }
}
