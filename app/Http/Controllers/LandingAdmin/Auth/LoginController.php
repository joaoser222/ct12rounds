<?php

namespace App\Http\Controllers\LandingAdmin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LandingAdmin\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('landing-admin/Login', [
            'status' => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('landing-admin.editor'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('landing_admin')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('landing-admin.login');
    }
}