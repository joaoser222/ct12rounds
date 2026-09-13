<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LandingAssetController extends Controller
{
    private const ALLOWED_EXTENSIONS = ['webp', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'ico'];

    public function show(string $path): BinaryFileResponse
    {
        $base = realpath(resource_path('views/landing-assets'));

        if ($base === false) {
            abort(404);
        }

        $file = realpath($base.DIRECTORY_SEPARATOR.urldecode($path));

        if ($file === false || ! str_starts_with($file, $base.DIRECTORY_SEPARATOR) || ! is_file($file)) {
            abort(404);
        }

        if (! in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), self::ALLOWED_EXTENSIONS, true)) {
            abort(404);
        }

        return response()->file($file, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}