<?php

namespace App\DTOs\LoyaltyLevels;

use App\DTOs\Contracts\BaseDTO;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class CreateLoyaltyLevelDTO extends BaseDTO
{
    public function __construct(
        #[Required, StringType, Max(100)]
        public string $name,

        #[Required, IntegerType]
        public int $min_months,

        #[Nullable, StringType, Max(7)]
        public ?string $color = null,

        #[Nullable, StringType, Max(500)]
        public ?string $description = null,
    ) {}
}
