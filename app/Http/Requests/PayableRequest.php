<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayableRequest extends FormRequest
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
            'supplier_id' => [$required, 'integer', 'min:1'],
            'due_date' => [$required, 'date'],
            'total' => [$required, 'numeric', 'min:0'],
            'payment_method' => [$required, 'string', 'in:pix,boleto,credit_card,cash'],
            'operation_type' => [$required, 'string', 'in:receivable,payable'],
            'annotations' => ['nullable', 'string', 'max:500'],
            'financial_account_id' => ['nullable', 'integer', 'min:1'],
            'financial_category_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
