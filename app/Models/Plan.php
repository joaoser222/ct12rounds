<?php

namespace App\Models;

use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'price',
        'duration_months',
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

    protected function modalityQuantity(): Attribute
    {
        return Attribute::get(fn (): int => $this->modalities()->count());
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
