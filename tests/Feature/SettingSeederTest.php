<?php

namespace Tests\Feature;

use App\Models\Setting;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_seeder_creates_the_expected_records_and_is_idempotent(): void
    {
        Setting::query()->create([
            'name' => 'sale_default_category',
            'label' => 'Categoria antiga',
            'content' => '15',
            'object_type' => 'int',
        ]);

        $this->seed(SettingSeeder::class);
        $this->seed(SettingSeeder::class);

        $this->assertDatabaseCount('settings', 23);

        $this->assertSame('', Setting::query()->where('name', 'contract_default_category')->value('content'));
        $this->assertSame('', Setting::query()->where('name', 'purchase_default_category')->value('content'));
        $this->assertSame('15', Setting::query()->where('name', 'sale_default_category')->value('content'));
        $this->assertSame('', Setting::query()->where('name', 'direct_lesson_default_category')->value('content'));
        $this->assertSame('', Setting::query()->where('name', 'default_financial_account')->value('content'));
        $this->assertSame('', Setting::query()->where('name', 'privacy_notice')->value('content'));
        $this->assertSame('', Setting::query()->where('name', 'image_rights_terms')->value('content'));
        $this->assertSame('25', Setting::query()->where('name', 'cancellation_fee_percentage')->value('content'));
        $this->assertSame('', Setting::query()->where('name', 'billing_failure_notification_email')->value('content'));
        $this->assertSame('1', Setting::query()->where('name', 'landing_enabled')->value('content'));
        $this->assertSame('Começar agora', Setting::query()->where('name', 'landing_main_cta_text')->value('content'));
        $this->assertSame('off', Setting::query()->where('name', 'site_mode')->value('content'));
        $this->assertSame('site', Setting::query()->where('name', 'site_mode')->value('group'));

        $this->assertSame('Categoria de Contratos', Setting::query()->where('name', 'contract_default_category')->value('label'));
        $this->assertSame('Categoria de Vendas', Setting::query()->where('name', 'sale_default_category')->value('label'));
        $this->assertSame('Aviso de Privacidade (LGPD)', Setting::query()->where('name', 'privacy_notice')->value('label'));
        $this->assertSame('Cláusula de Direitos de Imagem', Setting::query()->where('name', 'image_rights_terms')->value('label'));
        $this->assertSame('select:financial-category', Setting::query()->where('name', 'contract_default_category')->value('object_type'));
        $this->assertSame('select:financial-account', Setting::query()->where('name', 'default_financial_account')->value('object_type'));
        $this->assertSame('textarea', Setting::query()->where('name', 'privacy_notice')->value('object_type'));
        $this->assertSame('textarea', Setting::query()->where('name', 'image_rights_terms')->value('object_type'));
        $this->assertSame('Percentual da multa de cancelamento (%)', Setting::query()->where('name', 'cancellation_fee_percentage')->value('label'));
        $this->assertSame('number', Setting::query()->where('name', 'cancellation_fee_percentage')->value('object_type'));
        $this->assertSame('E-mail para avisos de falha de cobrança', Setting::query()->where('name', 'billing_failure_notification_email')->value('label'));
        $this->assertSame('text', Setting::query()->where('name', 'billing_failure_notification_email')->value('object_type'));

        $this->assertSame('billing', Setting::query()->where('name', 'contract_default_category')->value('group'));
        $this->assertSame('billing', Setting::query()->where('name', 'sale_default_category')->value('group'));
        $this->assertSame('billing', Setting::query()->where('name', 'cancellation_fee_percentage')->value('group'));
        $this->assertSame('billing', Setting::query()->where('name', 'billing_failure_notification_email')->value('group'));
        $this->assertSame('financial', Setting::query()->where('name', 'default_financial_account')->value('group'));
        $this->assertSame('peoples', Setting::query()->where('name', 'privacy_notice')->value('group'));
        $this->assertSame('peoples', Setting::query()->where('name', 'image_rights_terms')->value('group'));
        $this->assertSame('landing', Setting::query()->where('name', 'landing_enabled')->value('group'));
        $this->assertSame('landing', Setting::query()->where('name', 'landing_meta_description')->value('group'));

        $this->assertSame(1, Setting::query()->where('name', 'contract_default_category')->count());
    }

    public function test_setting_seeder_does_not_recreate_the_removed_contact_keys(): void
    {
        $this->seed(SettingSeeder::class);

        $this->assertDatabaseMissing('settings', ['name' => 'default_country_code']);
        $this->assertDatabaseMissing('settings', ['name' => 'hiring_terms']);
    }
}
