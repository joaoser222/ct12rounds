<?php

namespace App\Http\Controllers\LandingAdmin;

use App\Http\Controllers\Controller;
use App\Services\LandingSettingsService;
use App\Services\LandingStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EditorController extends Controller
{
    public function __construct(
        private readonly LandingSettingsService $landingSettings,
        private readonly LandingStorageService $landingStorage,
    ) {}

    public function index(): Response
    {
        $settings = $this->landingSettings->resolved();

        return Inertia::render('landing-admin/Editor', [
            'project' => $this->landingStorage->project(),
            'status' => $this->landingStorage->status(),
            'publishedAt' => $this->landingStorage->publishedAt()?->toISOString(),
            'draftHtml' => $this->landingStorage->draftHtml(),
            'templateSeed' => [
                'subtitle' => $settings['subtitle'],
                'ctaText' => $settings['ctaText'],
                'whatsappUrl' => $settings['whatsappUrl'],
            ],
            'publishUrl' => route('landing-admin.api.publish'),
            'contentUrl' => route('landing-admin.api.content'),
            'imageUploadUrl' => route('landing-admin.api.images'),
            'viewUrl' => route('home'),
        ]);
    }

    public function saveDraft(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project' => ['required', 'array'],
            'html' => ['required', 'string'],
            'css' => ['nullable', 'string'],
        ]);

        $this->landingStorage->saveDraft($data['project'], $this->composeHtml($data['html'], $data['css'] ?? ''));

        return response()->json(['ok' => true]);
    }

    public function publish(): JsonResponse
    {
        $result = $this->landingStorage->publish();

        return response()->json([
            'ok' => true,
            'publishedAt' => $result['publishedAt'],
        ]);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $file = $request->file('file');

        if ($file === null) {
            return response()->json(['error' => 'Arquivo não enviado.'], 422);
        }

        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();

        $path = $file->storeAs('landing', $filename, 'public');

        return response()->json([
            'url' => route('landing.storage', [basename((string) $path)]),
        ]);
    }

    private function composeHtml(string $html, string $css): string
    {
        return ($css === '' ? '' : "<style>{$css}</style>").$html;
    }
}