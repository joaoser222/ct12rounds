<?php

namespace App\DTOs\Contracts;

use App\Models\Contract;
use Spatie\LaravelData\Data;

class ContractResultDTO extends Data
{
    public function __construct(
        public int $id,
        public string $plan_name,
        public string $status,
        public float $total,
        public int $installments,
        public string $first_due_date,
        public ?int $client_id,
        public ?int $coupon_id,
        public int $plan_id,
        public string $created_at,
        public ?string $registration_token,
    ) {}

    public static function fromModel(Contract $contract): static
    {
        return new static(
            id: $contract->id,
            plan_name: $contract->plan_name,
            status: $contract->status->value,
            total: $contract->total,
            installments: $contract->installments,
            first_due_date: $contract->first_due_date?->format('Y-m-d') ?? '',
            client_id: $contract->client_id,
            coupon_id: $contract->coupon_id,
            plan_id: $contract->plan_id,
            created_at: $contract->created_at?->toISOString() ?? '',
            registration_token: $contract->registration_token,
        );
    }
}
