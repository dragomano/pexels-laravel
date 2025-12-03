<?php

declare(strict_types=1);

namespace Bugo\PexelsLaravel\Services;

use Devscast\Pexels\Client;
use Devscast\Pexels\Exception\NetworkException;
use Devscast\Pexels\Parameter\SearchParameters;
use Exception;
use Illuminate\Support\Facades\Cache;

class PexelsService
{
    protected Client $client;

    protected int $maxRequestsPerHour;

    protected int $maxRequestsPerMonth;

    protected bool $cacheEnabled;

    protected int $cacheTtl;

    public function __construct()
    {
        $this->client = new Client(token: config('pexels.api_key'));
        $this->maxRequestsPerHour = config('pexels.rate_limits.hourly', 200);
        $this->maxRequestsPerMonth = config('pexels.rate_limits.monthly', 20000);
        $this->cacheEnabled = config('pexels.cache.enabled', true);
        $this->cacheTtl = config('pexels.cache.ttl', 3600);
    }

    public function getRandomImage(string $query = 'nature'): array
    {
        $cacheKey = "pexels_batch_$query";

        if (! $this->cacheEnabled) {
            return $this->fetchSingleRandomImage($query);
        }

        $images = Cache::get($cacheKey);

        if (empty($images)) {
            $images = $this->fetchBatch($query);

            if (empty($images)) {
                return $this->getFallbackImageData($query);
            }

            Cache::put($cacheKey, $images, $this->cacheTtl);
        }

        $image = array_shift($images);

        if (! empty($images)) {
            Cache::put($cacheKey, $images, $this->cacheTtl);
        } else {
            Cache::forget($cacheKey);
        }

        return $image;
    }

    public function getFallbackImageData(string $query = 'nature'): array
    {
        $fallbacks = config('pexels.fallback_images', []);

        return $fallbacks[$query]
            ?? $fallbacks['nature']
            ?? [
                'url' => 'https://images.pexels.com/photos/414612/pexels-photo-414612.jpeg',
                'photographer' => 'Pexels',
                'photographer_url' => 'https://www.pexels.com/@pexels',
                'pexels_url' => 'https://www.pexels.com/photo/414612/',
                'alt' => 'Beautiful nature landscape',
            ];
    }

    public function getUsageStats(): array
    {
        $hourlyKey = 'pexels_requests_hour_' . now()->format('Y-m-d-H');
        $monthlyKey = 'pexels_requests_month_' . now()->format('Y-m');

        return [
            'hourly' => Cache::get($hourlyKey, 0),
            'hourly_limit' => $this->maxRequestsPerHour,
            'monthly' => Cache::get($monthlyKey, 0),
            'monthly_limit' => $this->maxRequestsPerMonth,
            'remaining_hourly' => $this->maxRequestsPerHour - Cache::get($hourlyKey, 0),
            'remaining_monthly' => $this->maxRequestsPerMonth - Cache::get($monthlyKey, 0),
        ];
    }

    protected function checkRateLimits(): bool
    {
        $hourlyKey = 'pexels_requests_hour_' . now()->format('Y-m-d-H');
        $monthlyKey = 'pexels_requests_month_' . now()->format('Y-m');

        $hourlyCount = Cache::get($hourlyKey, 0);
        $monthlyCount = Cache::get($monthlyKey, 0);

        if ($hourlyCount >= $this->maxRequestsPerHour) {
            report(new Exception('Pexels API hourly limit exceeded'));
            return false;
        }

        if ($monthlyCount >= $this->maxRequestsPerMonth) {
            report(new Exception('Pexels API monthly limit exceeded'));
            return false;
        }

        Cache::put($hourlyKey, $hourlyCount + 1, now()->addHour());
        Cache::put($monthlyKey, $monthlyCount + 1, now()->addMonth());

        return true;
    }

    protected function fetchBatch(string $query): array
    {
        if (! $this->checkRateLimits()) {
            return [];
        }

        try {
            $response = $this->client->searchPhotos(
                $query,
                new SearchParameters(per_page: 80)
            );

            if ($response->total_results === 0 || empty($response->photos)) {
                return [];
            }

            $batch = [];

            foreach ($response->photos as $photo) {
                $batch[] = [
                    'url' => $photo->src->original,
                    'photographer' => $photo->photographer,
                    'photographer_url' => $photo->photographer_url,
                    'pexels_url' => $photo->url,
                    'alt' => $photo->alt ?? "$query image",
                    'query' => $query,
                    'from_api' => true,
                ];
            }

            return $batch;

        } catch (NetworkException $e) {
            report($e);
        }

        return [];
    }

    protected function fetchSingleRandomImage(string $query): array
    {
        $batch = $this->fetchBatch($query);

        if (empty($batch)) {
            return $this->getFallbackImageData($query);
        }

        return $batch[array_rand($batch)];
    }
}
