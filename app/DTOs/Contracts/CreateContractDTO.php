<?php

namespace App\DTOs\Contracts;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class CreateContractDTO extends BaseDTO
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public int $plan_id,

        #[Required, IntegerType, Min(1)]
        public int $installments,

        #[Nullable, IntegerType]
        public ?int $coupon_id = null,

        #[Nullable, StringType, Max(500)]
        public ?string $annotations = null,
    ) {}
}