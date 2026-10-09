<?php

namespace App\Http\Middleware;

use App\Services\SiteModeService;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shows the public "under construction" / "maintenance" placeholder to guests
 * while keeping the authenticated team and critical integrations working.
 */
class HandleSiteMode
{
    public function __construct(private readonly SiteModeService $siteMode) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null) {
            return $next($request);
        }

        if ($request->routeIs('login', 'login.store', 'logout', 'password.*', 'gateway-postbacks.*')) {
            return $next($request);
        }

        $mode = $this->siteMode->mode();

        if (! $mode->isActive()) {
            return $next($request);
        }

        $content = $this->siteMode->content();

        $response = Inertia::render('SiteMode', [
            'title' => $content['title'],
            'message' => $content['message'],
        ])->toResponse($request);

        return $response->setStatusCode(503)->header('Retry-After', '3600');
    }
}
