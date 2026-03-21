<?php

declare(strict_types=1);

use Bugo\PexelsLaravel\Commands\CheckPexelsUsage;
use Bugo\PexelsLaravel\Services\PexelsService;
use Illuminate\Contracts\Console\Kernel;

it('registers the pexels service as a singleton and merges default config', function () {
    $first = app(PexelsService::class);
    $second = app(PexelsService::class);

    expect($first)->toBeInstanceOf(PexelsService::class)
        ->and($second)->toBe($first)
        ->and(config('pexels.rate_limits.hourly'))->toBe(200)
        ->and(config('pexels.rate_limits.monthly'))->toBe(20000);
});

it('registers the usage command in the artisan kernel', function () {
    $commands = app(Kernel::class)->all();

    expect($commands)->toHaveKey('pexels:usage')
        ->and($commands['pexels:usage'])->toBeInstanceOf(CheckPexelsUsage::class);
});
