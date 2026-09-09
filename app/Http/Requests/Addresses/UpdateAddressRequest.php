<?php

namespace App\Http\Requests\Addresses;

use App\Models\Address;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        $address = $this->route('address');

        return $address instanceof Address
            && ($this->user()?->can('update', $address) ?? false);
    }

    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'string', 'max:50'],
            'recipient_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:30'],
            'line_one' => ['sometimes', 'string', 'max:255'],
            'line_two' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'province' => ['sometimes', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}