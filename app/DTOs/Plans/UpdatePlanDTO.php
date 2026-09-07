<?php

namespace App\DTOs\Plans;

use App\DTOs\Contracts\BaseDTO;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class UpdatePlanDTO extends BaseDTO
{
    /** @param array<int, int> $plan_modalities */
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public int $id,

        #[Required, StringType, Max(255)]
        public string $name,

        #[Required, IntegerType, Min(1)]
        public int $plan_category_id,

        #[Nullable, StringType, Max(500)]
        public ?string $description,

        #[Required, Numeric, Min(0)]
        public float $price,

        #[Required, IntegerType, Min(1)]
        public int $duration_months,

        #[ArrayType(IntegerType::class)]
        public array $plan_modalities = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new static(
            id: $data['id'],
            name: $data['name'],
            plan_category_id: $data['plan_category_id'],
            description: $data['description'] ?? null,
            price: $data['price'],
            duration_months: $data['duration_months'],
            plan_modalities: array_map(
                fn (mixed $modalityId): int => (int) $modalityId,
                $data['plan_modalities'] ?? [],
            ),
        );
    }
}
