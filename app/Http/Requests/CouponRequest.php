<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CouponRequest extends FormRequest
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
        $required = $this->isMethod('POST') ? 'required' : 'nullable';

        return [
            'code' => [$required, 'string', 'max:50'],
            'percent' => [$required, 'numeric', 'min:0'],
            'discount_limit' => ['nullable', 'numeric', 'min:0'],
            'duration' => ['nullable', 'string', 'max:50'],
            'expiration_date' => ['nullable', 'date'],
            'plan_ids' => ['nullable', 'array'],
            'plan_ids.*' => ['integer', 'exists:plans,id'],
        ];
    }
}
