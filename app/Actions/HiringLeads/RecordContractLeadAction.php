<?php

namespace App\Actions\HiringLeads;

use App\Actions\BaseAction;
use App\Enums\HiringLeadSource;
use App\Enums\HiringLeadStatus;
use App\Models\Client;
use App\Models\Contract;
use App\Models\HiringLead;
use Carbon\CarbonImmutable;

/**
 * Records the lead created by the public contract registration.
 *
 * A contract can legitimately hold two leads: the pre-registration created on
 * the landing (`source = site`, linked when the contract was created) and the
 * one produced by the QR Code registration (`source = contract`). Only the
 * latter is reused, so a pre-registration is never rewritten and keeps its own
 * history. Reusing it on a retry is what stops a declined card from piling up
 * one duplicate lead per attempt.
 */
class RecordContractLeadAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = HiringLead::class;

    /**
     * @param  array<string, mixed>  $data  Contact data from the request, already normalized.
     */
    protected function handle(mixed $input): HiringLead
    {
        if (! is_array($input)) {
            throw new \InvalidArgumentException('RecordContractLeadAction expects an array of validated data.');
        }

        $contract = $input['contract'] ?? null;
        $client = $input['client'] ?? null;

        if (! $contract instanceof Contract || ! $client instanceof Client) {
            throw new \InvalidArgumentException('RecordContractLeadAction requires a contract and a client.');
        }

        $attributes = $this->attributesFrom($input, $contract, $client);

        $lead = $this->reusableLead($contract);

        if ($lead === null) {
            return HiringLead::query()->create($attributes);
        }

        $lead->update($attributes);

        return $lead->refresh();
    }

    /**
     * The lead this contract already owns from a previous QR registration.
     */
    private function reusableLead(Contract $contract): ?HiringLead
    {
        return HiringLead::query()
            ->where('contract_id', $contract->getKey())
            ->where('source', HiringLeadSource::CONTRACT->value)
            ->where('status', '!=', HiringLeadStatus::CONVERTED->value)
            ->latest('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributesFrom(array $data, Contract $contract, Client $client): array
    {
        $plan = $contract->plan;

        return [
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'document' => $client->document,
            'gender' => $client->gender,
            'birth_date' => $client->birth_date,
            'address' => $client->address,
            'address_number' => $client->address_number,
            'address_complement' => $client->address_complement,
            'address_district' => $client->address_district,
            'address_state' => $client->address_state,
            'address_city' => $client->address_city,
            'address_postal_code' => $client->address_postal_code,
            'status' => HiringLeadStatus::NEW->value,
            'source' => HiringLeadSource::CONTRACT->value,
            'payment_method' => $contract->payment_method?->value,
            'audience_category' => $client->audience_category?->value,
            'legal_representative_name' => $client->legal_representative_name,
            'legal_representative_document' => $client->legal_representative_document,
            'legal_representative_birth_date' => $client->legal_representative_birth_date,
            'visibility' => 'visible',
            'accepted_at' => CarbonImmutable::now(),
            'plan_id' => $plan?->getKey(),
            'coupon_id' => $contract->coupon_id,
            'contract_id' => $contract->getKey(),
            'client_id' => $client->getKey(),
        ];
    }
}
