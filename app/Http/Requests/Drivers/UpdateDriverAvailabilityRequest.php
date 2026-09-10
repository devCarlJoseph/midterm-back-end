<?php

namespace App\Http\Requests\Drivers;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDriverAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Driver;
    }

    public function rules(): array
    {
        return [
            'is_available_for_delivery' => ['required', 'boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (
                    ! $this->boolean('is_available_for_delivery')
                    || $validator->errors()->hasAny(['latitude', 'longitude'])
                ) {
                    return;
                }

                if ($this->input('latitude') === null || $this->input('longitude') === null) {
                    $validator->errors()->add(
                        'latitude',
                        'Latitude and longitude are required when becoming available.',
                    );
                }
            },
        ];
    }
}
