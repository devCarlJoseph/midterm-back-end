<?php

namespace App\Http\Requests\Stores;

use App\Enums\DeliveryOptionName;
use App\Models\DeliveryOption;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliveryOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('store')) ?? false;
    }

    public function rules(): array
    {
        /** @var Store $store */
        $store = $this->route('store');

        /** @var DeliveryOption $deliveryOption */
        $deliveryOption = $this->route('deliveryOption');

        return [
            'name' => [
                'sometimes',
                Rule::enum(DeliveryOptionName::class),
                Rule::unique('delivery_options', 'name')
                    ->where('store_id', $store->id)
                    ->ignore($deliveryOption),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'additional_fee' => ['sometimes', 'numeric', 'min:0', 'max:10000'],
            'estimated_delivery_minutes' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}