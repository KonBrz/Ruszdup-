<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Filament::serving(function () {
            // tylko zalogowani admini mogą używać panelu
            if (!auth()->check() || !auth()->user()->is_admin) {
                abort(403, 'Access denied');
            }
        });
    }
}
