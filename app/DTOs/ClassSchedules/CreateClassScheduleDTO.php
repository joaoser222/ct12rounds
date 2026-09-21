<?php

namespace App\DTOs\ClassSchedules;

use App\DTOs\Contracts\BaseDTO;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class CreateClassScheduleDTO extends BaseDTO
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public int $modality_id,

        #[Required, IntegerType, Min(1), Max(6)]
        public int $week_day,

        #[Required, StringType, Max(5)]
        public string $start_time,

        #[Required, StringType, Max(5)]
        public string $end_time,
    ) {}
}
