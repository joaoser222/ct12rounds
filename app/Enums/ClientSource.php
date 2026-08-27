<?php

namespace App\Enums;

use App\Traits\HasMetadata;

enum ClientSource: string
{
    use HasMetadata;

    case SITE = 'site';
    case RECEPTION = 'reception';
    case INDICATION = 'indication';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::SITE => 'Site',
            self::RECEPTION => 'Recepção',
            self::INDICATION => 'Indicação',
            self::MANUAL => 'Manual'
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SITE => 'info',
            self::RECEPTION => 'primary',
            self::INDICATION => 'warning',
            self::MANUAL => 'secondary',
        };
    }
}
