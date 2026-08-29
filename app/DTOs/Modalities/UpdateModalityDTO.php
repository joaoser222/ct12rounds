<?php

namespace App\DTOs\Modalities;

use App\DTOs\Contracts\BaseDTO;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class UpdateModalityDTO extends BaseDTO
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public int $id,

        #[Required, StringType, Max(255)]
        public string $name,

        #[Nullable, StringType, Max(7)]
        public ?string $color = null,

        #[Nullable, StringType, Max(255)]
        public ?string $icon = null,
    ) {}
}
