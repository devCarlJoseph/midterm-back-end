<?php

namespace App\Http\Requests\Stores;

use Illuminate\Foundation\Http\FormRequest;

class DeleteDeliveryOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('store')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
