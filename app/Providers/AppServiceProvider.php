<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Publishing side effects are registered by DomainObserverServiceProvider.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Laravel\Horizon\Horizon::auth(function ($request): bool {
            $user = $request->user();
            return $user && ! $user->suspended_at && $user->hasVerifiedEmail()
                && $user->hasAnyRole(['admin', 'super-admin']);
        });
    }
}
