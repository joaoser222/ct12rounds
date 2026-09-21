<?php

namespace App\Http\Controllers;

use App\Actions\HiringLeads\CreateSiteLeadAction;
use App\Http\Requests\PublicHiringLeadRequest;
use App\Models\ClassSchedule;
use App\Services\LandingSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

class HomeController extends Controller
{
    public function __construct(
        private readonly CreateSiteLeadAction $createSiteLead,
        private readonly LandingSettingsService $landingSettings,
    ) {}

    public function index(Request $request): InertiaResponse|RedirectResponse
    {
        if (auth()->check()) {
            return inertia('Home');
        }

        $settings = $this->landingSettings->resolved();

        if (! $settings['enabled']) {
            return redirect()->route('login');
        }

        $contractToken = $request->query('contract');

        $contract = is_string($contractToken) && $contractToken !== '' ? $contractToken : null;

        return inertia('public/Landing', [
            'settings' => $this->landingSettings->raw(),
            'success' => (bool) $request->session()->pull('landing_success'),
            'contract' => $contract,
            'registerUrl' => $contract !== null ? route('public.register', [
                'contract' => $contract,
                ...(is_string($request->query('plan')) && $request->query('plan') !== '' ? ['plan' => $request->query('plan')] : []),
                ...(is_string($request->query('coupon')) && $request->query('coupon') !== '' ? ['coupon' => $request->query('coupon')] : []),
            ]) : null,
            'storeUrl' => route('public.landing.store'),
            'title' => $settings['title'],
            'description' => $settings['description'],
            'whatsappUrl' => $settings['whatsappUrl'],
            'subtitle' => $settings['subtitle'],
            'ctaText' => $settings['ctaText'],
            'schedules' => ClassSchedule::query()
                ->where('class_schedules.visibility', 'visible')
                ->join('modalities', 'class_schedules.modality_id', '=', 'modalities.id')
                ->select([
                    'class_schedules.week_day',
                    'class_schedules.start_time',
                    'class_schedules.end_time',
                    'modalities.name as modality_name',
                    'modalities.color as modality_color',
                ])
                ->orderBy('class_schedules.week_day')
                ->orderBy('class_schedules.start_time')
                ->get()
                ->all(),
        ]);
    }

    public function store(PublicHiringLeadRequest $request): RedirectResponse
    {
        if (! $this->landingSettings->resolved()['enabled']) {
            return redirect()->route('home');
        }

        $this->createSiteLead->execute($request->validated());

        $request->session()->put('landing_success', true);

        return redirect()->route('home');
    }
}