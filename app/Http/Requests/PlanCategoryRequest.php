<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanCategoryRequest extends FormRequest
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
        $nameRule = $this->isMethod('POST') ? 'required' : 'nullable';

        return [
            'name' => [$nameRule, 'string', 'max:255'],
        ];
    }
}
