<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Storefront customer authentication — separate from back-office User auth.
 *
 * Routes  (prefix: /store/auth, name: website.auth.)
 *   GET  /login    — showLogin
 *   POST /login    — login
 *   GET  /register — showRegister
 *   POST /register — register
 *   POST /logout   — logout
 */
class CustomerAuthController extends Controller
{
    private const GUARD = 'customer';

    /* ---------------------------------------------------------------
     |  Login
     | --------------------------------------------------------------- */

    public function showLogin()
    {
        if (auth(self::GUARD)->check()) {
            return redirect()->route('website.account.profile');
        }

        return view('website.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $customer = Customer::where('email', $request->email)
            ->where('status', true)
            ->first();

        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        if (empty($customer->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'This account was created by the store. Please use the "Forgot password" link to set a password.']);
        }

        auth(self::GUARD)->login($customer, $request->boolean('remember'));

        $request->session()->regenerate();

        // Redirect to the page they came from (e.g. checkout) or profile
        $intended = session()->pull('url.customer_intended', route('website.account.profile'));

        return redirect($intended)->with('success', 'Welcome back, ' . $customer->name . '!');
    }

    /* ---------------------------------------------------------------
     |  Register
     | --------------------------------------------------------------- */

    public function showRegister()
    {
        if (auth(self::GUARD)->check()) {
            return redirect()->route('website.account.profile');
        }

        return view('website.auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'                  => ['required', 'string', 'max:191'],
            'email'                 => ['required', 'email', 'max:191', 'unique:customers,email'],
            'phone'                 => ['nullable', 'string', 'max:30'],
            'password'              => ['required', 'confirmed', Password::min(8)],
        ], [
            'email.unique' => 'An account with this email already exists. Please log in.',
        ]);

        $customer = Customer::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'phone'         => $request->phone,
            'password'      => Hash::make($request->password),
            'customer_type' => Customer::TYPE_RETAIL,
            'status'        => true,
        ]);

        auth(self::GUARD)->login($customer);

        $request->session()->regenerate();

        $intended = session()->pull('url.customer_intended', route('website.account.profile'));

        return redirect($intended)->with('success', 'Account created! Welcome to Sukaina Gems, ' . $customer->name . '.');
    }

    /* ---------------------------------------------------------------
     |  Logout
     | --------------------------------------------------------------- */

    public function logout(Request $request)
    {
        auth(self::GUARD)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('website.home')->with('success', 'You have been logged out.');
    }
}
