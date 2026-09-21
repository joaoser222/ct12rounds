<?php

namespace App\DTOs\ModalityCategories;

use App\DTOs\Contracts\BaseDTO;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class UpdateModalityCategoryDTO extends BaseDTO
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public int $id,

        #[Nullable, StringType, Max(100)]
        public ?string $name = null,

        #[Nullable, StringType, Max(100)]
        public ?string $slug = null,

        #[Nullable, StringType, Max(10)]
        public ?string $audience = null,
    ) {}
}
