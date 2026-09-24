<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatewayCustomer extends Model
{
    protected $table = 'gateway_customers';

    protected $fillable = [
        'gateway_reference_key',
        'holder_id',
        'holder_type',
        'gateway_account_id',
        'gateway_postback_id',
    ];

    protected $appends = [
        'holder_name',
    ];

    protected $morphMaps = [
        'holder_type' => [
            'client' => Client::class,
            'supplier' => Supplier::class,
            'trainer' => Trainer::class,
        ],
    ];

    public function holder()
    {
        return $this->morphTo();
    }

    public function getHolderNameAttribute(): ?string
    {
        $holder = $this->holder;

        return is_string($holder?->name) ? $holder->name : null;
    }

    public function gatewayAccount()
    {
        return $this->belongsTo(GatewayAccount::class);
    }

    public function gatewayPostback()
    {
        return $this->belongsTo(GatewayPostback::class);
    }
}
