<?php

namespace App\Enums;

enum Audience: string
{
    case ADULT = 'adult';
    case CHILD = 'child';

    public function label(): string
    {
        return match ($this) {
            self::ADULT => 'Adulto',
            self::CHILD => 'Infantil',
        };
    }
}
