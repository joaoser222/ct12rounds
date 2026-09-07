<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanRequest extends FormRequest
{
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
            'name' => ['required', 'string', 'max:255'],
            'plan_category_id' => ['required', 'integer', Rule::exists('plan_categories', 'id')],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_months' => ['required', 'integer', 'min:1'],
            'plan_modalities' => ['nullable', 'array'],
            'plan_modalities.*' => ['integer', 'distinct', Rule::exists('modalities', 'id')],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'plan_modalities' => $this->input('plan_modalities', []),
        ]);
    }
}
