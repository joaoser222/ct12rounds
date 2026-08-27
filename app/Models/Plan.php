<?php

namespace App\Models;

use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Plan extends Model
{
    use HasVisibility;

    protected $table = 'plans';

    protected $fillable = [
        'name',
        'description',
        'public_slug',
        'modality_quantity',
        'plan_category_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (Plan $plan): void {
            if ($plan->public_slug !== null) {
                return;
            }

            $baseSlug = Str::slug($plan->name);
            $slug = $baseSlug;
            $suffix = 2;

            while (
                Plan::query()
                    ->where('public_slug', $slug)
                    ->whereKeyNot($plan->getKey())
                    ->exists()
            ) {
                $slug = $baseSlug.'-'.$suffix;
                $suffix++;
            }

            $plan->public_slug = $slug;
        });
    }

    public function tiers()
    {
        return $this->hasMany(PlanTier::class);
    }

    public function planCategory()
    {
        return $this->belongsTo(PlanCategory::class);
    }

    public function modalities()
    {
        return $this->hasMany(PlanModality::class);
    }
}
