<?php

namespace App\Http\Requests;

use App\Enums\AudienceCategory;
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
        $isContractFlow = $this->boolean('is_contract_flow');
        $requiresLegalRep = $this->boolean('requires_legal_representative');
        $isLandingStore = $this->routeIs('public.landing.store');

        if ($isContractFlow) {
            return [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'phone' => ['required', 'string', 'min:10', 'max:14'],
                'document' => [
                    'required',
                    'string',
                    'min:11',
                    'max:14',
                ],
                'gender' => ['required', 'string', Rule::enum(GenderType::class)],
                'birth_date' => ['required', 'date'],
                'address' => ['required', 'string', 'max:200'],
                'address_number' => ['required', 'string', 'max:10'],
                'address_complement' => ['nullable', 'string', 'max:100'],
                'address_district' => ['required', 'string', 'max:100'],
                'address_state' => ['required', 'string', 'size:2'],
                'address_city' => ['required', 'string', 'max:100'],
                'address_postal_code' => ['required', 'string', 'max:8'],
                'contract' => ['required', 'string', 'max:64'],
                'accepted' => ['required', 'accepted'],
                'audience_category' => ['nullable', Rule::enum(AudienceCategory::class)],
                'legal_representative_name' => [$requiresLegalRep ? 'required' : 'nullable', 'string', 'max:255'],
                'legal_representative_document' => [$requiresLegalRep ? 'required' : 'nullable', 'string', 'min:11', 'max:14'],
                'legal_representative_birth_date' => [$requiresLegalRep ? 'required' : 'nullable', 'date'],
                'card_number' => ['required', 'string', 'min:13', 'max:19'],
                'card_expiry_month' => ['required', 'string', 'size:2'],
                'card_expiry_year' => ['required', 'string', 'size:4'],
                'card_cvv' => ['required', 'string', 'min:3', 'max:4'],
                'card_holder_name' => ['required', 'string', 'max:255'],
            ];
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:14'],
            'document' => [
                'required',
                'string',
                'min:11',
                'max:14',
            ],
            'coupon' => ['nullable', 'string', 'max:64'],
        ];

        if ($isLandingStore) {
            $rules['accepted'] = ['required', 'accepted'];
        }

        return $rules;
    }
}
