<?php

namespace App\Actions\HiringLeads;

use App\Actions\BaseAction;
use App\DTOs\Contracts\ActionResultDTO;
use App\Enums\ClientSource;
use App\Enums\ClientStatus;
use App\Enums\GenderType;
use App\Enums\HiringLeadStatus;
use App\Models\HiringLead;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Support\Carbon;

class ConvertHiringLeadAction extends BaseAction
{
    protected string $ability = '';

    protected string $modelClass = HiringLead::class;

    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof HiringLead) {
            throw new \InvalidArgumentException('ConvertHiringLeadAction requires a HiringLead model.');
        }

        $lead = $input;

        if ($lead->status === HiringLeadStatus::CONVERTED) {
            return ActionResultDTO::failure('Este pré-cadastro já foi convertido.');
        }

        $duplicateLead = HiringLead::query()
            ->where('email', $lead->email)
            ->where('phone', $lead->phone)
            ->whereKeyNot($lead->getKey())
            ->where('status', '!=', HiringLeadStatus::CONVERTED->value)
            ->exists();

        if ($duplicateLead) {
            return ActionResultDTO::failure('Já existe um pré-cadastro não convertido com este e-mail e telefone.');
        }

        $client = $this->clientRepository->findByEmailAndPhone($lead->email, $lead->phone);

        if ($client === null) {
            $client = $this->clientRepository->create([
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'document' => $lead->document,
                'gender' => $lead->gender ?? GenderType::MALE->value,
                'birth_date' => $lead->birth_date?->format('Y-m-d'),
                'address' => $lead->address,
                'address_number' => $lead->address_number,
                'address_complement' => $lead->address_complement,
                'address_district' => $lead->address_district,
                'address_state' => $lead->address_state,
                'address_city' => $lead->address_city,
                'address_postal_code' => $lead->address_postal_code,
                'audience_category' => $lead->audience_category?->value,
                'legal_representative_name' => $lead->legal_representative_name,
                'legal_representative_document' => $lead->legal_representative_document,
                'legal_representative_birth_date' => $lead->legal_representative_birth_date?->format('Y-m-d'),
                'status' => ClientStatus::ACTIVE->value,
                'client_source' => ClientSource::SITE->value,
            ]);
        }

        $lead->update([
            'client_id' => $client->getKey(),
            'converted_at' => Carbon::now(),
            'status' => HiringLeadStatus::CONVERTED->value,
        ]);

        return ActionResultDTO::success(
            ['client_id' => $client->getKey()],
            'Pré-cadastro convertido em cliente com sucesso.'
        );
    }
}
