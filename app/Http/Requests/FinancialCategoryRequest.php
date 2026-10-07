<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinancialCategoryRequest extends FormRequest
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
                'name' => ['required', 'string', 'max:255'],
                'color' => ['nullable', 'string', 'max:7'],
                'operation_type' => ['required', 'string', 'in:receivable,payable'],
                'cost_center_id' => ['nullable', 'integer', 'min:1'],
            ];
        }

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
            'operation_type' => ['nullable', 'string', 'in:receivable,payable'],
            'cost_center_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
