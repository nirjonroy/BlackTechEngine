<?php

namespace App\Providers;

use App\Models\Admin;
use App\View\Composers\FrontendLayoutComposer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (mixed $user, string $ability): ?bool {
            if (! $user instanceof Admin || Auth::guard('admin')->id() !== $user->getAuthIdentifier()) {
                return null;
            }

            return $user->hasRole('Super Admin', 'admin') ? true : null;
        });

        View::composer('frontend.app', FrontendLayoutComposer::class);
    }
}
