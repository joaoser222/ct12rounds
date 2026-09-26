<?php

namespace App\DTOs\Contracts;

use App\Enums\Gateway\GatewaySyncMode;
use Spatie\LaravelData\Data;

class ApplyContractDTO extends Data
{
    public function __construct(
        public readonly int $contractId,
        public readonly GatewaySyncMode $syncMode = GatewaySyncMode::QUEUE,
    ) {}
}
