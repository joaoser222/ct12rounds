<?php

namespace App\DTOs\LoyaltyLevels;

use App\DTOs\Contracts\BaseDTO;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;

class UpdateLoyaltyLevelDTO extends BaseDTO
{
    public function __construct(
        public int $id,

        #[Nullable, StringType, Max(100)]
        public ?string $name = null,

        #[Nullable, IntegerType]
        public ?int $min_months = null,

        #[Nullable, StringType, Max(7)]
        public ?string $color = null,

        #[Nullable, StringType, Max(500)]
        public ?string $description = null,
    ) {}
}
