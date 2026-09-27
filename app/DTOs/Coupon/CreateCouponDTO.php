<?php

namespace App\DTOs\Coupon;

use App\DTOs\Contracts\BaseDTO;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class CreateCouponDTO extends BaseDTO
{
    /** @param array<int, int> $plan_ids */
    public function __construct(
        #[Required, StringType, Max(50)]
        public string $code,

        #[Required, Numeric, Min(0)]
        public float $percent,

        #[Nullable, Numeric, Min(0)]
        public ?float $discount_limit = null,

        #[Nullable, StringType, Max(50)]
        public ?string $duration = null,

        #[Nullable, Date]
        public ?string $expiration_date = null,

        #[ArrayType(IntegerType::class)]
        public array $plan_ids = [],
    ) {}
}
