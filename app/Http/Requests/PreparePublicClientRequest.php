<?php

namespace App\Http\Requests;

use App\Enums\AudienceCategory;
use App\Enums\GenderType;
use App\Models\Contract;
use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreparePublicClientRequest extends FormRequest
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
        $contract = Contract::query()
            ->where('registration_token', $this->input('contract'))
            ->first();

        /** @var Plan|null $plan */
        $plan = $contract?->plan;

        $requiresLegalRep = $plan?->requiresLegalRepresentative() ?? false;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:14'],
            'document' => ['required', 'string', 'min:11', 'max:14'],
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
            'audience_category' => ['nullable', Rule::enum(AudienceCategory::class)],
            'legal_representative_name' => [$requiresLegalRep ? 'required' : 'nullable', 'string', 'max:255'],
            'legal_representative_document' => [$requiresLegalRep ? 'required' : 'nullable', 'string', 'min:11', 'max:14'],
            'legal_representative_birth_date' => [$requiresLegalRep ? 'required' : 'nullable', 'date'],
        ];
    }
}
