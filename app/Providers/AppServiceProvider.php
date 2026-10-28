<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\AiProvider;
use App\View\Composers\FrontendLayoutComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('ai-provider-verification', function (Request $request): array {
            $adminIdentifier = $request->user('admin')?->getAuthIdentifier() ?? $request->ip();
            $provider = $request->route('aiProvider');
            $providerIdentifier = $provider instanceof AiProvider
                ? $provider->getKey()
                : (string) $provider;

            return [
                Limit::perMinute(3)->by('admin-provider-verification:'.$adminIdentifier),
                Limit::perMinute(5)->by('provider-verification:'.$providerIdentifier),
            ];
        });

        Gate::before(function (mixed $user, string $ability): ?bool {
            if (! $user instanceof Admin || Auth::guard('admin')->id() !== $user->getAuthIdentifier()) {
                return null;
            }

            return $user->hasRole('Super Admin', 'admin') ? true : null;
        });

        View::composer('frontend.app', FrontendLayoutComposer::class);
    }
}
