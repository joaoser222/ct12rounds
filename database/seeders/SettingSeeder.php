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
            ['name' => 'contract_default_category', 'label' => 'Categoria de Contratos', 'content' => '', 'object_type' => 'select:financial-category', 'group' => 'billing'],
            ['name' => 'purchase_default_category', 'label' => 'Categoria de Compras', 'content' => '', 'object_type' => 'select:financial-category', 'group' => 'billing'],
            ['name' => 'sale_default_category', 'label' => 'Categoria de Vendas', 'content' => '', 'object_type' => 'select:financial-category', 'group' => 'billing'],
            ['name' => 'direct_lesson_default_category', 'label' => 'Categoria de Aula Avulsa', 'content' => '', 'object_type' => 'select:financial-category', 'group' => 'billing'],
            ['name' => 'default_financial_account', 'label' => 'Conta Padrão', 'content' => '', 'object_type' => 'select:financial-account', 'group' => 'financial'],
            ['name' => 'hiring_terms', 'label' => 'Termos de Pré-cadastro', 'content' => '', 'object_type' => 'textarea', 'group' => 'peoples'],
            ['name' => 'image_rights_terms', 'label' => 'Cláusula de Direitos de Imagem', 'content' => '', 'object_type' => 'textarea', 'group' => 'peoples'],
            ['name' => 'cancellation_fee_percentage', 'label' => 'Percentual da multa de cancelamento (%)', 'content' => '25', 'object_type' => 'number', 'group' => 'billing'],
            ['name' => 'landing_enabled', 'label' => 'Landing page ativa', 'content' => '1', 'object_type' => 'boolean', 'group' => 'landing'],
            ['name' => 'landing_hero_title', 'label' => 'Landing - Título principal', 'content' => 'CT 12 Rounds — Centro de Treinamento', 'object_type' => 'text', 'group' => 'landing'],
            ['name' => 'landing_hero_subtitle', 'label' => 'Landing - Subtítulo', 'content' => 'Boxe, Kickboxing e Jiu-Jitsu com a Metodologia 12 Rounds. Treinamentos que transformam — do iniciante ao atleta competitivo.', 'object_type' => 'textarea', 'group' => 'landing'],
            ['name' => 'landing_main_cta_text', 'label' => 'Landing - Texto do botão de cadastro', 'content' => 'Começar agora', 'object_type' => 'text', 'group' => 'landing'],
            ['name' => 'landing_whatsapp_phone', 'label' => 'Landing - WhatsApp (E.164)', 'content' => '', 'object_type' => 'text', 'group' => 'landing'],
            ['name' => 'landing_about_title', 'label' => 'Landing - Título da seção institucional', 'content' => 'Sobre a CT12', 'object_type' => 'text', 'group' => 'landing'],
            ['name' => 'landing_about_text', 'label' => 'Landing - Texto institucional', 'content' => '', 'object_type' => 'textarea', 'group' => 'landing'],
            ['name' => 'landing_meta_title', 'label' => 'Landing - Meta title (SEO)', 'content' => 'CT 12 Rounds — Centro de Treinamento', 'object_type' => 'text', 'group' => 'landing'],
            ['name' => 'landing_meta_description', 'label' => 'Landing - Meta description (SEO)', 'content' => 'Boxe, Kickboxing e Jiu-Jitsu em Palmas — TO. Metodologia 12 Rounds: defesa pessoal, condicionamento e alta performance.', 'object_type' => 'textarea', 'group' => 'landing'],
        ])->each(function (array $attributes): void {
            $setting = Setting::query()->firstOrNew(['name' => $attributes['name']]);

            $setting->label = $attributes['label'];
            $setting->object_type = $attributes['object_type'];
            $setting->group = $attributes['group'];

            if (! $setting->exists || $setting->getRawOriginal('content') === '0') {
                $setting->content = $attributes['content'];
            }

            $setting->save();
        });
    }
}
