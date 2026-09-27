<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Settings that no longer have a consumer.
     *
     * `default_country_code` moved to `config/contact.php` as a fixed value,
     * and `hiring_terms` was superseded by the contract template rendered in
     * `PublicHiringLeadController`. The seeder upserts, so removing the rows
     * from it would not delete the existing records and they would keep
     * showing up as editable fields in the settings screen.
     *
     * @var array<int, string>
     */
    private const ORPHANED = ['default_country_code', 'hiring_terms'];

    /**
     * @var array<string, array<string, string>>
     */
    private const DEFAULTS = [
        'default_country_code' => [
            'name' => 'default_country_code',
            'label' => 'Código de País dos Telefones',
            'content' => '55',
            'object_type' => 'text',
            'group' => 'general',
        ],
        'hiring_terms' => [
            'name' => 'hiring_terms',
            'label' => 'Termos de Pré-cadastro',
            'content' => '',
            'object_type' => 'textarea',
            'group' => 'peoples',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->whereIn('name', self::ORPHANED)->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        foreach (self::ORPHANED as $name) {
            Setting::query()->firstOrCreate(['name' => $name], self::DEFAULTS[$name]);
        }
    }
};
