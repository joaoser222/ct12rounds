<?php

namespace App\Models;

use App\PaymentGateways\PaymentGatewayManager;
use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GatewayAccount extends Model
{
    use HasFactory, HasVisibility;

    protected $table = 'gateway_accounts';

    protected $fillable = [
        'name',
        'description',
        'invoicing_enabled',
        'invoicing_supported',
        'invoicing_configured',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'invoicing_enabled' => 'boolean',
        'invoicing_supported' => 'boolean',
        'invoicing_configured' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (GatewayAccount $account): void {
            $definition = app(PaymentGatewayManager::class)->find((string) $account->name);
            $account->invoicing_supported = $definition?->supportsInvoicing() === true;

            // Fiscal configuration is considered valid when the emitter has the
            // minimum municipal data (service description and service code).
            // The settings.invoicing.fiscal_configuration_at flag indicates that
            // the configuration was applied at the provider (PUT /invoices/municipalConfiguration).
            $account->invoicing_configured = $account->invoicing_supported
                && filled(data_get($account->settings, 'invoicing.service_description'))
                && filled(data_get($account->settings, 'invoicing.municipal_service_code'));
        });
    }

    public function customers()
    {
        return $this->hasMany(GatewayCustomer::class);
    }

    public function scopeInvoicingEligible(Builder $query): void
    {
        $query
            ->where('invoicing_supported', true)
            ->where('invoicing_configured', true)
            ->where('invoicing_enabled', true);
    }

    public function isInvoicingEligible(): bool
    {
        return $this->invoicing_supported
            && $this->invoicing_configured
            && $this->invoicing_enabled;
    }
}
