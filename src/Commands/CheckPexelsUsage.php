<?php

declare(strict_types=1);

namespace Bugo\PexelsLaravel\Commands;

use Bugo\PexelsLaravel\Services\PexelsService;
use Illuminate\Console\Command;

class CheckPexelsUsage extends Command
{
    protected $signature = 'pexels:usage';

    protected $description = 'Check Pexels API usage statistics';

    public function handle(PexelsService $service): void
    {
        $stats = $service->getUsageStats();

        $this->info('Pexels API Usage Statistics');
        $this->newLine();

        $this->table(
            ['Period', 'Used', 'Limit', 'Remaining', 'Percentage'],
            [
                [
                    'Hourly',
                    $stats['hourly'],
                    $stats['hourly_limit'],
                    $stats['remaining_hourly'],
                    round(($stats['hourly'] / $stats['hourly_limit']) * 100, 2) . '%'
                ],
                [
                    'Monthly',
                    $stats['monthly'],
                    $stats['monthly_limit'],
                    $stats['remaining_monthly'],
                    round(($stats['monthly'] / $stats['monthly_limit']) * 100, 2) . '%'
                ]
            ]
        );

        if ($stats['remaining_hourly'] < 20) {
            $this->warn('Hourly limit is running low!');
        }

        if ($stats['remaining_monthly'] < 1000) {
            $this->warn('Monthly limit is running low!');
        }
    }
}
