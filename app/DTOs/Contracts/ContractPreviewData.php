<?php

namespace App\DTOs\Contracts;

use App\Enums\AudienceCategory;
use App\Models\Client;
use App\Models\Contract;
use App\Models\HiringLead;
use App\Models\Plan;
use App\Services\CancellationFeeService;

final class ContractPreviewData
{
    /**
     * @param  array<string, bool|string>  $values
     */
    private function __construct(private readonly array $values) {}

    public static function from(
        CancellationFeeService $cancellationFeeService,
        Contract $contract,
        ?HiringLead $lead = null,
        ?Client $client = null,
    ): self {
        $contract->loadMissing(['client', 'plan']);

        if ($lead === null) {
            $linkedLead = $contract->hiringLeads()->latest('id')->first();
            $lead = $linkedLead instanceof HiringLead ? $linkedLead : null;
        }

        $contractClient = $contract->client instanceof Client ? $contract->client : null;
        $plan = $contract->plan instanceof Plan ? $contract->plan : null;
        $subject = $client ?? $contractClient ?? $lead;
        $isMinor = $plan?->audience === AudienceCategory::CHILD;
        $address = self::address($subject);
        $cityState = self::cityState($subject);
        $total = (float) $contract->total;
        $cancellationFeePercentage = $cancellationFeeService->effectivePercentage(
            $plan?->cancellation_fee_percentage,
        );

        return new self([
            'contractant_name' => self::string($subject?->name),
            'contractant_nationality' => '',
            'contractant_marital_status' => '',
            'contractant_profession' => '',
            'contractant_rg' => '',
            'contractant_cpf' => self::string($subject?->document),
            'contractant_address' => $address,
            'contractant_city_state' => $cityState,
            'contractant_email' => self::string($subject?->email),
            'contracted_name' => (string) config('app.name'),
            'contracted_cnpj' => '',
            'contracted_address' => '',
            'contracted_city_state' => '',
            'plan_name' => self::string($contract->plan_name ?: $plan?->name),
            'plan_price' => self::currency($total),
            'plan_installments' => (string) ($contract->installments ?? 1),
            'plan_duration' => self::duration($plan),
            'first_due_date' => $contract->first_due_date?->format('d/m/Y') ?? '',
            'academy_address' => '',
            'opening_hours' => '',
            'scheduled_time' => '',
            'contract_duration' => self::duration($plan),
            'cancellation_fee_percentage' => self::percentage($cancellationFeePercentage),
            'cancellation_fee_value' => self::currency(
                (float) $cancellationFeeService->feeValue($total, $plan?->cancellation_fee_percentage),
            ),
            'forum_city' => self::string($subject?->address_city),
            'forum_state' => self::string($subject?->address_state),
            'is_minor' => $isMinor,
            'minor_name' => self::string($subject?->name),
            'minor_birth_date' => $subject?->birth_date?->format('d/m/Y') ?? '',
            'legal_representative_name' => self::string($subject?->legal_representative_name),
            'legal_representative_document' => self::string($subject?->legal_representative_document),
            'legal_representative_relationship' => 'responsável legal',
            'contract_date' => now()->format('d/m/Y'),
        ]);
    }

    /**
     * @return array<string, bool|string>
     */
    public function toArray(): array
    {
        return $this->values;
    }

    private static function address(Client|HiringLead|null $subject): string
    {
        if ($subject === null) {
            return '';
        }

        return implode(', ', array_filter([
            $subject->address,
            $subject->address_number,
            $subject->address_complement,
            $subject->address_district,
            $subject->address_city,
            $subject->address_state,
            $subject->address_postal_code,
        ], fn (mixed $part): bool => filled($part)));
    }

    private static function cityState(Client|HiringLead|null $subject): string
    {
        if ($subject === null) {
            return '';
        }

        return implode('/', array_filter([
            $subject->address_city,
            $subject->address_state,
        ], fn (mixed $part): bool => filled($part)));
    }

    private static function duration(?Plan $plan): string
    {
        $months = $plan?->duration_months;

        if ($months === null) {
            return 'Não informado';
        }

        return $months === 1 ? '1 mês' : $months.' meses';
    }

    private static function currency(float $value): string
    {
        return 'R$ '.number_format($value, 2, ',', '.');
    }

    private static function percentage(float $value): string
    {
        return fmod($value, 1.0) === 0.0
            ? number_format($value, 0, ',', '.')
            : rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    }

    private static function string(mixed $value): string
    {
        return is_string($value) || is_numeric($value) ? (string) $value : '';
    }
}
