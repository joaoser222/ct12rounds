<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Resolves the public landing display settings from stored settings,
 * applying the same defaults used by the static landing design.
 */
class LandingSettingsService
{
    /**
     * @return array<string, string>
     */
    public function raw(): array
    {
        $names = [
            'landing_enabled',
            'landing_hero_title',
            'landing_hero_subtitle',
            'landing_main_cta_text',
            'landing_whatsapp_phone',
            'landing_about_title',
            'landing_about_text',
            'landing_meta_title',
            'landing_meta_description',
        ];

        $stored = Setting::query()
            ->whereIn('name', $names)
            ->pluck('content', 'name');

        return array_merge(
            array_fill_keys($names, ''),
            $stored->map(fn ($value): string => $value === null ? '' : (string) $value)->all(),
        );
    }

    /**
     * @return array{
     *     enabled: bool,
     *     title: string,
     *     description: string,
     *     whatsappUrl: string,
     *     subtitle: string,
     *     ctaText: string,
     * }
     */
    public function resolved(): array
    {
        $settings = $this->raw();

        return [
            'enabled' => filter_var($settings['landing_enabled'], FILTER_VALIDATE_BOOLEAN),
            'title' => $settings['landing_meta_title'] !== '' ? $settings['landing_meta_title'] : 'CT 12 Rounds — Centro de Treinamento',
            'description' => $settings['landing_meta_description'] !== '' ? $settings['landing_meta_description'] : 'Boxe, Kickboxing e Jiu-Jitsu em Palmas — TO. Metodologia 12 Rounds: defesa pessoal, condicionamento e alta performance.',
            'whatsappUrl' => 'https://wa.me/'.($settings['landing_whatsapp_phone'] !== '' ? $settings['landing_whatsapp_phone'] : '5563981019160'),
            'subtitle' => $settings['landing_hero_subtitle'] !== '' ? $settings['landing_hero_subtitle'] : 'Boxe, Kickboxing e Jiu-Jitsu com a Metodologia 12 Rounds. Treinamentos que transformam — do iniciante ao atleta competitivo.',
            'ctaText' => $settings['landing_main_cta_text'] !== '' ? $settings['landing_main_cta_text'] : 'Começar agora',
        ];
    }
}