<?php

namespace App\Enums;

use App\Traits\HasMetadata;

enum ClientStatus: string
{
    use HasMetadata;

    case ACTIVE = 'active';
    case PENDING = 'pending';
    case INACTIVE = 'inactive';
    case OVERDUED = 'overdued';
    case LOCKED = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Ativo',
            self::PENDING => 'Pendente',
            self::INACTIVE => 'Inativo',
            self::OVERDUED => 'Em atraso',
            self::LOCKED => 'Bloqueado'
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::PENDING => 'info',
            self::INACTIVE => 'secondary',
            self::OVERDUED => 'warning',
            self::LOCKED => 'error',
        };
    }
}
