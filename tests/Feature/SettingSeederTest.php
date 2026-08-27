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

        $this->assertDatabaseCount('settings', 6);

        $this->assertSame('', Setting::query()->where('name', 'contract_default_category')->value('content'));
        $this->assertSame('', Setting::query()->where('name', 'purchase_default_category')->value('content'));
        $this->assertSame('15', Setting::query()->where('name', 'sale_default_category')->value('content'));
        $this->assertSame('', Setting::query()->where('name', 'direct_lesson_default_category')->value('content'));
        $this->assertSame('', Setting::query()->where('name', 'default_financial_account')->value('content'));
        $this->assertSame('', Setting::query()->where('name', 'hiring_terms')->value('content'));

        $this->assertSame('Categoria de Contratos', Setting::query()->where('name', 'contract_default_category')->value('label'));
        $this->assertSame('Categoria de Vendas', Setting::query()->where('name', 'sale_default_category')->value('label'));
        $this->assertSame('select:financial-category', Setting::query()->where('name', 'contract_default_category')->value('object_type'));
        $this->assertSame('select:financial-account', Setting::query()->where('name', 'default_financial_account')->value('object_type'));
        $this->assertSame('textarea', Setting::query()->where('name', 'hiring_terms')->value('object_type'));

        $this->assertSame(1, Setting::query()->where('name', 'contract_default_category')->count());
    }
}
