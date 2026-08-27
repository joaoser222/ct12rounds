<?php

namespace App\Enums;

use App\Traits\HasMetadata;

enum HiringLeadSource: string
{
    use HasMetadata;

    case SITE = 'site';
    case CONTRACT = 'contract';

    public function label(): string
    {
        return match ($this) {
            self::SITE => 'Site (promoção)',
            self::CONTRACT => 'Contrato (QR code)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SITE => 'info',
            self::CONTRACT => 'primary',
        };
    }
}
