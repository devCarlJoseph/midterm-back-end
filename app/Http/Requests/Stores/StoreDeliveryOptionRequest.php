<?php

namespace App\Http\Requests\Stores;

use App\Enums\DeliveryOptionName;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeliveryOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('store')) ?? false;
    }

    public function rules(): array
    {
        /** @var Store $store */
        $store = $this->route('store');

        return [
            'name' => [
                'required',
                Rule::enum(DeliveryOptionName::class),
                Rule::unique('delivery_options', 'name')
                    ->where('store_id', $store->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'additional_fee' => ['required', 'numeric', 'min:0', 'max:10000'],
            'estimated_delivery_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
