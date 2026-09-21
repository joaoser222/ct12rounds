<?php

namespace App\DTOs\ClassSchedules;

use App\DTOs\Contracts\BaseDTO;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class UpdateClassScheduleDTO extends BaseDTO
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public int $id,

        #[Nullable, IntegerType, Min(1)]
        public ?int $modality_id = null,

        #[Nullable, IntegerType, Min(1), Max(6)]
        public ?int $week_day = null,

        #[Nullable, StringType, Max(5)]
        public ?string $start_time = null,

        #[Nullable, StringType, Max(5)]
        public ?string $end_time = null,
    ) {}
}
