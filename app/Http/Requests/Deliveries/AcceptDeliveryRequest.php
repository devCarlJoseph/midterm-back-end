<?php

namespace App\Http\Requests\Deliveries;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class AcceptDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Driver;
    }

    public function rules(): array
    {
        return [];
    }
}