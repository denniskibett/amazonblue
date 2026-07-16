<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('system.helper', function () {
            return new \App\Helpers\SystemHelper();
        });
    }

    public function boot(): void
    {
    }
}