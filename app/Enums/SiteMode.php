<?php

namespace App\Enums;

enum SiteMode: string
{
    case OFF = 'off';
    case CONSTRUCTION = 'construction';
    case MAINTENANCE = 'maintenance';

    public function isActive(): bool
    {
        return $this !== self::OFF;
    }
}
