<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LandingStorageController extends Controller
{
    public function show(string $path): StreamedResponse
    {
        $safePath = 'landing/'.basename($path);

        if (! Storage::disk('public')->exists($safePath)) {
            abort(404);
        }

        return Storage::disk('public')->response($safePath, null, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}