<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoyaltyLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->isMethod('POST')) {
            return [
                'name' => ['required', 'string', 'max:100'],
                'min_months' => ['required', 'integer', 'min:0'],
                'color' => ['nullable', 'string', 'max:7'],
                'description' => ['nullable', 'string', 'max:500'],
            ];
        }

        return [
            'name' => ['nullable', 'string', 'max:100'],
            'min_months' => ['nullable', 'integer', 'min:0'],
            'color' => ['nullable', 'string', 'max:7'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
