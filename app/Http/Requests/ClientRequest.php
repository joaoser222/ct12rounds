<?php

namespace App\Http\Requests;

use App\Enums\ClientStatus;
use App\Enums\GenderType;
use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
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
        /** @var Client|null $client */
        $client = $this->route('client');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:11'],
            'document' => [
                'required',
                'string',
                'size:11',
                Rule::unique('clients', 'document')->ignore($client?->id),
            ],
            'gender' => ['required', 'string', Rule::enum(GenderType::class)],
            'birth_date' => ['required', 'date'],
            'legal_representative' => ['boolean'],
            'legal_representative_name' => ['nullable', 'string', 'max:255'],
            'legal_representative_document' => ['nullable', 'string', 'size:11'],
            'legal_representative_birth_date' => ['nullable', 'date'],
            'address_postal_code' => ['required', 'string', 'max:8'],
            'address' => ['nullable', 'string', 'max:200'],
            'address_number' => ['required', 'string', 'max:10'],
            'address_complement' => ['nullable', 'string', 'max:100'],
            'address_district' => ['nullable', 'string', 'max:100'],
            'address_state' => ['nullable', 'string', 'size:2'],
            'address_city' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ClientStatus::class)],
        ];
    }
}
