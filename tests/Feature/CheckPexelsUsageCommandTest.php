<?php

declare(strict_types=1);

use Bugo\PexelsLaravel\Services\PexelsService;

it('renders usage statistics without warnings when enough quota remains', function () {
    $service = mock(PexelsService::class);
    $service->shouldReceive('getUsageStats')->once()->andReturn([
        'hourly' => 10,
        'hourly_limit' => 200,
        'monthly' => 250,
        'monthly_limit' => 20000,
        'remaining_hourly' => 190,
        'remaining_monthly' => 19750,
    ]);

    app()->instance(PexelsService::class, $service);

    $this->artisan('pexels:usage')
        ->expectsOutput('Pexels API Usage Statistics')
        ->assertSuccessful();
});

it('shows warnings when hourly and monthly quotas are running low', function () {
    $service = mock(PexelsService::class);
    $service->shouldReceive('getUsageStats')->once()->andReturn([
        'hourly' => 195,
        'hourly_limit' => 200,
        'monthly' => 19550,
        'monthly_limit' => 20000,
        'remaining_hourly' => 5,
        'remaining_monthly' => 450,
    ]);

    app()->instance(PexelsService::class, $service);

    $this->artisan('pexels:usage')
        ->expectsOutput('Pexels API Usage Statistics')
        ->expectsOutput('Hourly limit is running low!')
        ->expectsOutput('Monthly limit is running low!')
        ->assertSuccessful();
});
