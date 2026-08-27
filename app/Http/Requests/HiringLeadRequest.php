<?php

namespace App\Http\Requests;

use App\Enums\GenderType;
use App\Enums\HiringLeadSource;
use App\Enums\HiringLeadStatus;
use App\Models\HiringLead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;

class HiringLeadRequest extends FormRequest
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
        /** @var HiringLead|null $lead */
        $lead = $this->route('hiring_lead');

        $addressRequired = new RequiredIf($this->input('source') === HiringLeadSource::CONTRACT->value);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:14'],
            'document' => [
                'required',
                'string',
                'min:11',
                'max:14',
                Rule::unique('hiring_leads', 'document')->ignore($lead?->id),
            ],
            'source' => ['required', Rule::enum(HiringLeadSource::class)],
            'status' => ['nullable', Rule::enum(HiringLeadStatus::class)],
            'gender' => ['nullable', 'string', Rule::enum(GenderType::class)],
            'birth_date' => ['nullable', 'date'],
            'address' => [$addressRequired, 'string', 'max:200'],
            'address_number' => [$addressRequired, 'string', 'max:10'],
            'address_complement' => ['nullable', 'string', 'max:100'],
            'address_district' => [$addressRequired, 'string', 'max:100'],
            'address_state' => [$addressRequired, 'string', 'size:2'],
            'address_city' => [$addressRequired, 'string', 'max:100'],
            'address_postal_code' => [$addressRequired, 'string', 'max:8'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'coupon_id' => ['nullable', 'integer', 'exists:coupons,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
        ];
    }
}
