<?php

namespace App\Enums;

use App\Traits\HasMetadata;

enum HiringLeadStatus: string
{
    use HasMetadata;

    case NEW = 'new';
    case CONTACTED = 'contacted';
    case CONVERTED = 'converted';
    case DISCARDED = 'discarded';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Novo',
            self::CONTACTED => 'Contatado',
            self::CONVERTED => 'Convertido',
            self::DISCARDED => 'Descartado'
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NEW => 'info',
            self::CONTACTED => 'warning',
            self::CONVERTED => 'success',
            self::DISCARDED => 'secondary',
        };
    }
}
