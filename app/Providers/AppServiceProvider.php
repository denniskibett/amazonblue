<?php

namespace App\Providers;

use Bavix\Wallet\WalletConfigure;
use Illuminate\Support\ServiceProvider;
use App\Models\System;
use App\Helpers\SystemHelper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The wallet tables are created by our own migrations (database/migrations, 0001_01_02_*),
        // so the package must not load its vendor copies as well.
        WalletConfigure::ignoreMigrations();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        System::updated(function ($system) {
            SystemHelper::clearCache();
        });
    }
}
