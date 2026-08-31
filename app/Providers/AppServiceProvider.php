<?php

namespace App\Providers;

use App\Services\CartService;
use App\Services\SettingService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register SettingService as a singleton so the cache is shared
        // across the entire request lifecycle.
        $this->app->singleton(SettingService::class, fn () => new SettingService());
    }

    /**
     * Bootstrap any application services.
     *
     * Custom Blade directives for RBAC:
     *   @role('admin')             ... @endrole
     *   @role('admin|manager')     ... @endrole       (OR-semantics, pipe-delimited)
     *   @permission('products.edit')                  (OR-semantics)
     *   @permission('products.create|products.edit')
     *   @anypermission('a','b','c')
     *   @allpermissions(['a','b'])
     */
    public function boot(): void
    {
        // ----- @role / @endrole -----
        Blade::if('role', function (string $roles) {
            $user = auth()->user();
            return $user && ($user->isSuperAdmin() || $user->hasAnyRole($roles));
        });

        // ----- @permission / @endpermission (accepts a pipe-delimited list) -----
        Blade::if('permission', function (string $permissions) {
            $user = auth()->user();
            return $user && $user->hasAnyPermission($permissions);
        });

        // ----- @anypermission(...slugs) / @endanypermission -----
        Blade::if('anypermission', function (...$slugs) {
            $user = auth()->user();
            return $user && $user->hasAnyPermission($slugs);
        });

        // ----- @allpermissions([slugs]) / @endallpermissions -----
        Blade::if('allpermissions', function (array $slugs) {
            $user = auth()->user();
            return $user && $user->hasAllPermissions($slugs);
        });

        // Branded reset-password email, replacing Laravel's default plain
        // notification template. toMailUsing()'s callback receives the raw
        // token (not a built URL) and fully bypasses resetUrl()/
        // createUrlUsing() inside ResetPassword::toMail(), so the reset
        // link is built here directly — pointing at the storefront's own
        // reset page, not the admin panel's (which has no password-reset
        // feature at all, so the 'password.reset' route Laravel's default
        // would build doesn't exist). Only customers ever trigger this in
        // practice — there's no equivalent "forgot password" flow wired up
        // for back-office User accounts — but this customization is global
        // to the ResetPassword notification class, so it applies to both.
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = route('website.auth.reset-password.show', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            return (new MailMessage())
                ->subject('Reset Your Password')
                ->view('emails.customer.reset-password', [
                    'customer'      => $notifiable,
                    'url'           => $url,
                    'expireMinutes' => config('auth.passwords.customers.expire', 60),
                ]);
        });

        // ----- Share $settings with ALL website.* views -----
        View::composer('website.*', function ($view) {
            $settings = app(SettingService::class);
            $view->with('settings', $settings);
        });

        // ----- Cart badge/drawer: re-validate against live Product state -----
        // website.layout reads the cart directly to render the navbar
        // badge and cart-drawer preview on EVERY storefront page -- not
        // just /cart or /checkout, which already run this through
        // CartService via their own controllers (see CartController,
        // CheckoutController). Without this, a product pulled from the
        // site mid-browsing session would still show here until the
        // customer happened to open the full cart page.
        View::composer('website.layout', function ($view) {
            $result = app(CartService::class)->validate(session('sg_cart', []));

            if ($result['removed']) {
                session(['sg_cart' => $result['cart']]);
            }

            $view->with('cart', $result['cart']);
        });

        // ----- Share $settings with the admin panel + auth pages -----
        // A '*' composer, not a named list: Blade's @extends() only
        // resolves the PARENT view (layout.app) after the CHILD view's
        // own @section blocks have already executed. A composer scoped
        // to 'layout.app' therefore never reaches $settings usages
        // inside child views themselves (e.g. settings/index.blade.php's
        // own markup) — only inside layout.app's own body/includes.
        // '*' catches every view, admin and storefront alike; cheap,
        // since it's just handing out the already-resolved singleton.
        View::composer('*', function ($view) {
            $view->with('settings', app(SettingService::class));
        });
    }
}
