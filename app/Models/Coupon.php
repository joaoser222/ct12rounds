<?php

namespace App\Models;

use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasVisibility;

    protected $table = 'coupons';

    protected $fillable = [
        'code',
        'percent',
        'discount_limit',
        'duration',
        'expiration_date',
        'max_uses',
        'used_count',
    ];

    protected $casts = [
        'expiration_date' => 'date:Y-m-d',
        'percent' => 'float',
        'discount_limit' => 'float',
        'max_uses' => 'integer',
        'used_count' => 'integer',
    ];

    public function isAvailable(): bool
    {
        if ($this->expiration_date?->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }
}
