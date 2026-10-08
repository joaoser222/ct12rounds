<?php

namespace App\Services;

use App\Enums\SiteMode;
use App\Models\Setting;

/**
 * Resolves the public site status (online, under construction or maintenance)
 * and the message shown on the placeholder page.
 */
class SiteModeService
{
    public function mode(): SiteMode
    {
        $value = Setting::query()->where('name', 'site_mode')->first()?->content;

        return SiteMode::tryFrom(is_string($value) ? $value : '') ?? SiteMode::OFF;
    }

    /**
     * @return array{title: string, message: string}
     */
    public function content(): array
    {
        $mode = $this->mode();

        $prefix = $mode === SiteMode::MAINTENANCE ? 'site_maintenance' : 'site_construction';

        $stored = Setting::query()
            ->whereIn('name', [$prefix.'_title', $prefix.'_message'])
            ->pluck('content', 'name');

        $defaults = $mode === SiteMode::MAINTENANCE
            ? [
                'title' => 'Estamos em manutenção',
                'message' => 'Estamos realizando uma manutenção programada. Voltamos em breve.',
            ]
            : [
                'title' => 'Estamos em construção',
                'message' => 'Estamos preparando tudo por aqui. Voltamos em breve.',
            ];

        $title = (string) ($stored[$prefix.'_title'] ?? '');
        $message = (string) ($stored[$prefix.'_message'] ?? '');

        return [
            'title' => $title !== '' ? $title : $defaults['title'],
            'message' => $message !== '' ? $message : $defaults['message'],
        ];
    }
}
