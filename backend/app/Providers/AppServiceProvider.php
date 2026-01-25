<?php

namespace App\Providers;

use App\Models\Task;
use App\Models\Trip;
use App\Policies\TaskPolicy;
use App\Policies\TripPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;

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
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        // Explicit policy mapping for CI stability (no auto-discovery dependency)
        Gate::policy(Trip::class, TripPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
    }
}
