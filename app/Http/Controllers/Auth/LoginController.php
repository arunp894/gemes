<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Email + password login / logout.
 *
 * Heavy lifting (validation, throttling, attempt) lives in
 * {@see LoginRequest}; this controller is the HTTP-layer wrapper.
 */
class LoginController extends Controller
{
    /**
     * Show the login form. If the user is already authenticated,
     * bounce them to the dashboard.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended(route('dashboard'));
        }

        return view('auth.login');
    }

    /**
     * Process the login form.
     * On success, regenerate the session (fixation defence) and
     * redirect to the originally requested URL (intended) or dashboard.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Log out and invalidate the session.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'You have been signed out.');
    }
}
