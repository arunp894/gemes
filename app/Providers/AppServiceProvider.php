<?php

namespace App\Providers;

use App\Services\SettingService;
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

        // ----- Share $settings with ALL website.* views -----
        View::composer('website.*', function ($view) {
            $settings = app(SettingService::class);
            $view->with('settings', $settings);
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
