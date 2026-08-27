<?php

namespace App\Models;

use App\Enums\HiringLeadSource;
use App\Enums\HiringLeadStatus;
use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiringLead extends Model
{
    use HasFactory, HasVisibility;

    protected $table = 'hiring_leads';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'document',
        'gender',
        'birth_date',
        'address',
        'address_number',
        'address_complement',
        'address_district',
        'address_state',
        'address_city',
        'address_postal_code',
        'status',
        'source',
        'accepted_at',
        'converted_at',
        'plan_id',
        'coupon_id',
        'client_id',
        'contract_id',
    ];

    protected $casts = [
        'status' => HiringLeadStatus::class,
        'source' => HiringLeadSource::class,
        'birth_date' => 'date:Y-m-d',
        'accepted_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => HiringLeadStatus::NEW,
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
