<?php

namespace App\Http\Requests;

use App\Services\Gateway\GatewayCustomerSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GatewayTransferRecipientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $holderName = $this->input('holder_name');

        if (is_string($holderName)) {
            $this->merge([
                'holder_name' => app(GatewayCustomerSanitizer::class)->sanitizeName($holderName),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $recipientId = $this->route('gateway_transfer_recipient');

        return [
            'gateway_account_id' => ['required', 'integer', Rule::exists('gateway_accounts', 'id')->where('visibility', 'visible')],
            'label' => [
                'required',
                'string',
                'max:100',
                Rule::unique('gateway_transfer_recipients', 'label')
                    ->where('gateway_account_id', $this->integer('gateway_account_id'))
                    ->ignore($recipientId),
            ],
            'holder_name' => ['required', 'string', 'max:255'],
            'holder_document' => ['required', 'string', 'max:20'],
            'pix_key' => ['required', 'string', 'max:255'],
            'pix_key_type' => ['required', 'string', Rule::in(['CPF', 'CNPJ', 'EMAIL', 'PHONE', 'EVP'])],
        ];
    }
}
