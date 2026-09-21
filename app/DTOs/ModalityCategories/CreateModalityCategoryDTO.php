<?php

namespace App\DTOs\ModalityCategories;

use App\DTOs\Contracts\BaseDTO;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class CreateModalityCategoryDTO extends BaseDTO
{
    public function __construct(
        #[Required, StringType, Max(100)]
        public string $name,

        #[Required, StringType, Max(100)]
        public string $slug,

        #[Required, StringType, Max(10)]
        public string $audience,
    ) {}
}
