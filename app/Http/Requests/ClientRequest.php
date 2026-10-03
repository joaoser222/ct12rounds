<?php

namespace App\Http\Requests;

use App\Enums\AudienceCategory;
use App\Enums\ClientStatus;
use App\Enums\GenderType;
use App\Models\Client;
use App\Models\ModalityGraduation;
use App\Rules\Cpf;
use App\Rules\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'phone' => ['required', 'string', new Phone],
            'document' => [
                'required',
                'string',
                new Cpf,
                Rule::unique('clients', 'document')->ignore($client?->id),
            ],
            'gender' => ['required', 'string', Rule::enum(GenderType::class)],
            'birth_date' => ['required', 'date'],
            'audience_category' => ['nullable', Rule::enum(AudienceCategory::class)],
            'legal_representative_name' => ['nullable', 'string', 'max:255'],
            'legal_representative_document' => ['nullable', 'string', 'min:11', 'max:14'],
            'legal_representative_birth_date' => ['nullable', 'date'],
            'address_postal_code' => ['required', 'string', 'max:8'],
            'address' => ['nullable', 'string', 'max:200'],
            'address_number' => ['required', 'string', 'max:10'],
            'address_complement' => ['nullable', 'string', 'max:100'],
            'address_district' => ['nullable', 'string', 'max:100'],
            'address_state' => ['nullable', 'string', 'size:2'],
            'address_city' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ClientStatus::class)],
            'graduations' => ['nullable', 'array'],
            'graduations.*.modality_id' => ['required', 'integer', 'exists:modalities,id'],
            'graduations.*.modality_graduation_id' => ['required', 'integer', 'exists:modality_graduations,id'],
            'graduations.*.promoted_at' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->assertGraduationsMatchTheirModality($validator);
        });
    }

    /**
     * The graduation must belong to the modality selected in the same row.
     */
    private function assertGraduationsMatchTheirModality(Validator $validator): void
    {
        foreach ((array) $this->input('graduations', []) as $index => $graduation) {
            if (! is_array($graduation)) {
                continue;
            }

            $belongsToModality = ModalityGraduation::query()
                ->whereKey($graduation['modality_graduation_id'] ?? null)
                ->where('modality_id', $graduation['modality_id'] ?? null)
                ->exists();

            if (! $belongsToModality) {
                $validator->errors()->add(
                    "graduations.{$index}.modality_graduation_id",
                    'A graduação informada não pertence à modalidade selecionada.',
                );
            }
        }
    }
}
