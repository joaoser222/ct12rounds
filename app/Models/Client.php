<?php

namespace App\Models;

use App\Enums\ClientSource;
use App\Enums\ClientStatus;
use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, HasVisibility;

    protected $table = 'clients';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'document',
        'birth_date',
        'gender',
        'profile_image',
        'address',
        'address_number',
        'address_complement',
        'address_state',
        'address_city',
        'address_district',
        'address_postal_code',
        'legal_representative',
        'legal_representative_name',
        'legal_representative_document',
        'legal_representative_birth_date',
        'trainer_id',
        'status',
        'client_source',
        'loyalty_level_id',
        'loyalty_streak_months',
        'loyalty_since',
    ];

    protected $casts = [
        'legal_representative' => 'boolean',
        'client_source' => ClientSource::class,
        'birth_date' => 'date:Y-m-d',
        'legal_representative_birth_date' => 'date:Y-m-d',
        'status' => ClientStatus::class,
        'loyalty_streak_months' => 'integer',
        'loyalty_since' => 'date:Y-m-d',
    ];

    protected $attributes = [
        'status' => ClientStatus::ACTIVE,
    ];

    public function loyaltyLevel(): BelongsTo
    {
        return $this->belongsTo(LoyaltyLevel::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'holder_id')->where('holder_type', 'client');
    }
}
