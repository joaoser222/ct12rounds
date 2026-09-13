<?php

namespace App\Http\Controllers;

use App\Actions\HiringLeads\CreateSiteLeadAction;
use App\Http\Requests\PublicHiringLeadRequest;
use App\Services\LandingSettingsService;
use App\Services\LandingStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

class HomeController extends Controller
{
    public function __construct(
        private readonly CreateSiteLeadAction $createSiteLead,
        private readonly LandingSettingsService $landingSettings,
        private readonly LandingStorageService $landingStorage,
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

        $publishedHtml = $this->landingStorage->publishedHtml();

        return inertia('public/Landing', [
            'settings' => $this->landingSettings->raw(),
            'contentHtml' => $publishedHtml === null || $publishedHtml === '' ? null : $publishedHtml,
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