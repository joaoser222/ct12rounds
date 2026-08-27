<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect([
            ['name' => 'contract_default_category', 'label' => 'Categoria de Contratos', 'content' => '', 'object_type' => 'select:financial-category'],
            ['name' => 'purchase_default_category', 'label' => 'Categoria de Compras', 'content' => '', 'object_type' => 'select:financial-category'],
            ['name' => 'sale_default_category', 'label' => 'Categoria de Vendas', 'content' => '', 'object_type' => 'select:financial-category'],
            ['name' => 'direct_lesson_default_category', 'label' => 'Categoria de Aula Avulsa', 'content' => '', 'object_type' => 'select:financial-category'],
            ['name' => 'default_financial_account', 'label' => 'Conta Padrão', 'content' => '', 'object_type' => 'select:financial-account'],
            ['name' => 'hiring_terms', 'label' => 'Termos de Pré-cadastro', 'content' => '', 'object_type' => 'textarea'],
        ])->each(function (array $attributes): void {
            $setting = Setting::query()->firstOrNew(['name' => $attributes['name']]);

            $setting->label = $attributes['label'];
            $setting->object_type = $attributes['object_type'];

            if (! $setting->exists || $setting->getRawOriginal('content') === '0') {
                $setting->content = $attributes['content'];
            }

            $setting->save();
        });
    }
}
