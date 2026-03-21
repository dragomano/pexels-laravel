<?php

declare(strict_types=1);

namespace Tests;

use Bugo\PexelsLaravel\PexelsServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PexelsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        /** @var Repository $config */
        $config = $app['config'];

        $config->set('pexels.api_key', 'test-api-key');
        $config->set('cache.default', 'array');
    }
}
