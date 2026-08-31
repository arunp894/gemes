<?php

namespace App\Http\Controllers;

use App\Mail\CustomerWelcomeMail;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password as PasswordBroker;
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

        // Checked before Hash::check(): a null/empty password always fails
        // Hash::check() anyway, so that generic branch would otherwise
        // swallow this case and never let a legacy/imported customer (no
        // password ever set) reach the more helpful message below.
        if ($customer && empty($customer->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'This account doesn\'t have a password set yet. Please use "Forgot password" below to create one.']);
        }

        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
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

        // A mail-server hiccup should never block a successful signup —
        // the account is already created, so log and move on.
        try {
            Mail::to($customer->email)->send(new CustomerWelcomeMail($customer));
        } catch (\Throwable $e) {
            logger()->error('Customer welcome email failed', ['customer_id' => $customer->id, 'message' => $e->getMessage()]);
        }

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

    /* ---------------------------------------------------------------
     |  Forgot / Reset Password
     | --------------------------------------------------------------- */

    public function showForgotPassword()
    {
        return view('website.auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        PasswordBroker::broker('customers')->sendResetLink($request->only('email'));

        // Same message regardless of whether the email matched an account —
        // avoids leaking which addresses are registered customers.
        return back()->with('success', 'If an account exists for that email, a password reset link has been sent.');
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('website.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $status = PasswordBroker::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Customer $customer, string $password) {
                $customer->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        return $status === PasswordBroker::PASSWORD_RESET
            ? redirect()->route('website.auth.login')->with('success', 'Your password has been set. Please log in.')
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
