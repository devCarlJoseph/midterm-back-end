<?php

namespace App\Http\Requests\Stores;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrowseStoresRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => [
                'nullable',
                'string',
                Rule::exists('categories', 'slug'),
            ],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
