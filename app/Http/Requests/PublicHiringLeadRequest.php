<?php

namespace App\Http\Requests;

use App\Enums\GenderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;

class PublicHiringLeadRequest extends FormRequest
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
        $contractFlow = new RequiredIf($this->input('plan') !== null || $this->input('contract') !== null);
        $addressRequired = $contractFlow;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:14'],
            'document' => [
                'required',
                'string',
                'min:11',
                'max:14',
                Rule::unique('hiring_leads', 'document'),
            ],
            'gender' => [$contractFlow, 'string', Rule::enum(GenderType::class)],
            'birth_date' => [$contractFlow, 'date'],
            'address' => [$addressRequired, 'string', 'max:200'],
            'address_number' => [$addressRequired, 'string', 'max:10'],
            'address_complement' => ['nullable', 'string', 'max:100'],
            'address_district' => [$addressRequired, 'string', 'max:100'],
            'address_state' => [$addressRequired, 'string', 'size:2'],
            'address_city' => [$addressRequired, 'string', 'max:100'],
            'address_postal_code' => [$addressRequired, 'string', 'max:8'],
            'plan' => ['nullable', 'string', 'max:255', Rule::exists('plans', 'public_slug')],
            'coupon' => ['nullable', 'string', 'max:50'],
            'contract' => ['nullable', 'string', 'max:64'],
            'accepted' => ['required', 'accepted'],
        ];
    }
}
