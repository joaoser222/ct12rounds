<?php

namespace App\Models;

use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    /**
     * Plans this coupon is restricted to. An empty relation means the coupon
     * applies to every plan.
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'coupon_plan');
    }

    public function appliesToPlan(int|string $planId): bool
    {
        $restricted = $this->plans;

        return $restricted->isEmpty() || $restricted->contains('id', (int) $planId);
    }

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
