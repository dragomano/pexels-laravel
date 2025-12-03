<?php

declare(strict_types=1);

namespace Bugo\PexelsLaravel;

use Illuminate\Support\ServiceProvider;
use Bugo\PexelsLaravel\Commands\CheckPexelsUsage;
use Bugo\PexelsLaravel\Services\PexelsService;

class PexelsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/pexels.php', 'pexels'
        );

        $this->app->singleton(PexelsService::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/pexels.php' => config_path('pexels.php'),
        ], 'pexels-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckPexelsUsage::class,
            ]);
        }
    }
}
