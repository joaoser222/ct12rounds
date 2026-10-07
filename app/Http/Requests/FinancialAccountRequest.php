<?php

namespace App\Http\Requests;

use App\Enums\FinancialAccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinancialAccountRequest extends FormRequest
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
        $isBankAccount = $this->input('account_type') === FinancialAccountType::BANK->value;

        $required = $this->isMethod('POST') ? 'required' : 'nullable';

        return [
            'name' => [$required, 'string', 'max:255'],
            'account_type' => [$required, Rule::enum(FinancialAccountType::class)],
            'holder_name' => [Rule::requiredIf($isBankAccount), 'nullable', 'string', 'max:255'],
            'holder_document' => [Rule::requiredIf($isBankAccount), 'nullable', 'string', 'max:20'],
            'holder_birth_date' => [Rule::requiredIf($isBankAccount), 'nullable', 'date'],
            'bank_account_number' => [Rule::requiredIf($isBankAccount), 'nullable', 'string', 'max:50'],
            'bank_agency' => [Rule::requiredIf($isBankAccount), 'nullable', 'string', 'max:50'],
            'bank_account_type' => [Rule::requiredIf($isBankAccount), 'nullable', 'string', 'max:20'],
            'bank_code' => [Rule::requiredIf($isBankAccount), 'nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        if (($data['account_type'] ?? null) === FinancialAccountType::CASH->value) {
            $data = [
                ...$data,
                'holder_name' => null,
                'holder_document' => null,
                'holder_birth_date' => null,
                'bank_account_number' => null,
                'bank_agency' => null,
                'bank_account_type' => null,
                'bank_code' => null,
            ];
        }

        return $data;
    }
}
