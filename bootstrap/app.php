<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->preventRequestForgery(except: [
            'gateway-postbacks/*/receive',
            'mcp/*',
            'chat/*',
            '/',
        ]);

        $middleware->redirectGuestsTo(fn (Request $request): string => $request->is('landing-admin') || $request->is('landing-admin/*')
            ? route('landing-admin.login')
            : route('login'));

        $middleware->redirectUsersTo(fn (Request $request): string => $request->is('landing-admin/*')
            ? '/landing-admin'
            : route('dashboard', absolute: false));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->expectsJson() || $request->is('api/*'),
        );
    })->create();
