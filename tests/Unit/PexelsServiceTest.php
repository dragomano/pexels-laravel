<?php

declare(strict_types=1);

use Bugo\PexelsLaravel\Services\PexelsService;
use Devscast\Pexels\Client;
use Illuminate\Support\Facades\Cache;

class TestablePexelsService extends PexelsService
{
    public function __construct(
        ?Client $client = null,
        int $maxRequestsPerHour = 200,
        int $maxRequestsPerMonth = 20000,
        bool $cacheEnabled = true,
        int $cacheTtl = 3600,
    ) {
        if ($client !== null) {
            $this->client = $client;
        }

        $this->maxRequestsPerHour = $maxRequestsPerHour;
        $this->maxRequestsPerMonth = $maxRequestsPerMonth;
        $this->cacheEnabled = $cacheEnabled;
        $this->cacheTtl = $cacheTtl;
    }

    public function callCheckRateLimits(): bool
    {
        return $this->checkRateLimits();
    }

    public function callFetchBatch(string $query): array
    {
        return $this->fetchBatch($query);
    }

    public function callFetchSingleRandomImage(string $query): array
    {
        return $this->fetchSingleRandomImage($query);
    }
}

beforeEach(function () {
    Cache::flush();

    config()->set('pexels.fallback_images', [
        'nature' => [
            'url' => 'https://example.com/nature.jpg',
            'photographer' => 'Nature Photographer',
            'photographer_url' => 'https://example.com/nature-photographer',
            'pexels_url' => 'https://example.com/nature',
            'alt' => 'Nature fallback',
        ],
        'technology' => [
            'url' => 'https://example.com/technology.jpg',
            'photographer' => 'Tech Photographer',
            'photographer_url' => 'https://example.com/tech-photographer',
            'pexels_url' => 'https://example.com/technology',
            'alt' => 'Technology fallback',
        ],
    ]);
});

it('returns a query specific fallback image', function () {
    $service = new PexelsService();

    expect($service->getFallbackImageData('technology'))
        ->toMatchArray([
            'url' => 'https://example.com/technology.jpg',
            'alt' => 'Technology fallback',
        ]);
});

it('falls back to the nature image when the query is unknown', function () {
    $service = new PexelsService();

    expect($service->getFallbackImageData('unknown'))
        ->toMatchArray([
            'url' => 'https://example.com/nature.jpg',
            'alt' => 'Nature fallback',
        ]);
});

it('returns usage stats based on cached counters', function () {
    config()->set('pexels.rate_limits.hourly', 200);
    config()->set('pexels.rate_limits.monthly', 20000);

    $hourlyKey = 'pexels_requests_hour_' . now()->format('Y-m-d-H');
    $monthlyKey = 'pexels_requests_month_' . now()->format('Y-m');

    Cache::put($hourlyKey, 12, now()->addHour());
    Cache::put($monthlyKey, 345, now()->addMonth());

    $service = new PexelsService();

    expect($service->getUsageStats())->toBe([
        'hourly' => 12,
        'hourly_limit' => 200,
        'monthly' => 345,
        'monthly_limit' => 20000,
        'remaining_hourly' => 188,
        'remaining_monthly' => 19655,
    ]);
});

it('serves cached images sequentially and clears the cache when the batch is exhausted', function () {
    config()->set('pexels.cache.enabled', true);
    config()->set('pexels.cache.ttl', 600);

    $service = new class extends PexelsService
    {
        public function __construct()
        {
            $this->cacheEnabled = true;
            $this->cacheTtl = 600;
        }
    };

    Cache::put('pexels_batch_nature', [
        ['url' => 'https://example.com/1.jpg', 'alt' => 'One'],
        ['url' => 'https://example.com/2.jpg', 'alt' => 'Two'],
    ], 600);

    $firstImage = $service->getRandomImage();
    $secondImage = $service->getRandomImage();

    expect($firstImage)->toBe(['url' => 'https://example.com/1.jpg', 'alt' => 'One'])
        ->and($secondImage)->toBe(['url' => 'https://example.com/2.jpg', 'alt' => 'Two'])
        ->and(Cache::get('pexels_batch_nature'))->toBeNull();
});

it('uses the non-cached fetch path when caching is disabled', function () {
    $service = new class extends PexelsService
    {
        public bool $singleRandomImageFetched = false;

        public function __construct()
        {
            $this->cacheEnabled = false;
        }

        protected function fetchSingleRandomImage(string $query): array
        {
            $this->singleRandomImageFetched = true;

            return [
                'url' => "https://example.com/$query.jpg",
                'alt' => "Image for $query",
            ];
        }
    };

    $image = $service->getRandomImage('forest');

    expect($service->singleRandomImageFetched)->toBeTrue()
        ->and($image)->toBe([
            'url' => 'https://example.com/forest.jpg',
            'alt' => 'Image for forest',
        ]);
});

it('returns fallback data when the fetched batch is empty', function () {
    config()->set('pexels.cache.enabled', true);
    config()->set('pexels.cache.ttl', 600);

    $service = new class extends PexelsService
    {
        public function __construct()
        {
            $this->cacheEnabled = true;
            $this->cacheTtl = 600;
        }

        protected function fetchBatch(string $query): array
        {
            return [];
        }
    };

    expect($service->getRandomImage('technology'))
        ->toMatchArray([
            'url' => 'https://example.com/technology.jpg',
            'alt' => 'Technology fallback',
        ]);
});

it('returns the built-in default fallback when no fallback config exists', function () {
    config()->set('pexels.fallback_images', []);

    $service = new PexelsService();

    expect($service->getFallbackImageData('unknown'))->toBe([
        'url' => 'https://images.pexels.com/photos/414612/pexels-photo-414612.jpeg',
        'photographer' => 'Pexels',
        'photographer_url' => 'https://www.pexels.com/@pexels',
        'pexels_url' => 'https://www.pexels.com/photo/414612/',
        'alt' => 'Beautiful nature landscape',
    ]);
});

it('stores a freshly fetched batch in cache before returning the first image', function () {
    $service = new class extends TestablePexelsService
    {
        protected function fetchBatch(string $query): array
        {
            return [
                ['url' => 'https://example.com/1.jpg', 'alt' => 'One'],
                ['url' => 'https://example.com/2.jpg', 'alt' => 'Two'],
            ];
        }
    };

    $image = $service->getRandomImage();

    expect($image)->toBe(['url' => 'https://example.com/1.jpg', 'alt' => 'One'])
        ->and(Cache::get('pexels_batch_nature'))->toBe([
            ['url' => 'https://example.com/2.jpg', 'alt' => 'Two'],
        ]);
});

it('increments cached counters when rate limits allow the request', function () {
    $service = new TestablePexelsService(maxRequestsPerHour: 2, maxRequestsPerMonth: 3);

    expect($service->callCheckRateLimits())->toBeTrue();

    $hourlyKey = 'pexels_requests_hour_' . now()->format('Y-m-d-H');
    $monthlyKey = 'pexels_requests_month_' . now()->format('Y-m');

    expect(Cache::get($hourlyKey))->toBe(1)
        ->and(Cache::get($monthlyKey))->toBe(1);
});

it('returns false when the hourly limit has been reached', function () {
    $hourlyKey = 'pexels_requests_hour_' . now()->format('Y-m-d-H');
    $monthlyKey = 'pexels_requests_month_' . now()->format('Y-m');

    Cache::put($hourlyKey, 2, now()->addHour());
    Cache::put($monthlyKey, 1, now()->addMonth());

    $service = new TestablePexelsService(maxRequestsPerHour: 2, maxRequestsPerMonth: 3);

    expect($service->callCheckRateLimits())->toBeFalse()
        ->and(Cache::get($hourlyKey))->toBe(2)
        ->and(Cache::get($monthlyKey))->toBe(1);
});

it('returns false when the monthly limit has been reached', function () {
    $hourlyKey = 'pexels_requests_hour_' . now()->format('Y-m-d-H');
    $monthlyKey = 'pexels_requests_month_' . now()->format('Y-m');

    Cache::put($hourlyKey, 1, now()->addHour());
    Cache::put($monthlyKey, 3, now()->addMonth());

    $service = new TestablePexelsService(maxRequestsPerHour: 2, maxRequestsPerMonth: 3);

    expect($service->callCheckRateLimits())->toBeFalse()
        ->and(Cache::get($hourlyKey))->toBe(1)
        ->and(Cache::get($monthlyKey))->toBe(3);
});

it('returns an empty batch immediately when rate limits are exceeded', function () {
    $hourlyKey = 'pexels_requests_hour_' . now()->format('Y-m-d-H');
    $monthlyKey = 'pexels_requests_month_' . now()->format('Y-m');

    Cache::put($hourlyKey, 2, now()->addHour());
    Cache::put($monthlyKey, 1, now()->addMonth());

    Client::$capturedQuery = null;
    Client::$capturedParameters = null;

    $service = new TestablePexelsService(maxRequestsPerHour: 2, maxRequestsPerMonth: 3);

    expect($service->callFetchBatch('forest'))->toBe([])
        ->and(Client::$capturedQuery)->toBeNull()
        ->and(Client::$capturedParameters)->toBeNull();
});

it('returns a random image from the fetched batch', function () {
    $service = new class extends TestablePexelsService
    {
        protected function fetchBatch(string $query): array
        {
            return [
                ['url' => 'https://example.com/only.jpg', 'alt' => 'Only image'],
            ];
        }
    };

    expect($service->callFetchSingleRandomImage('forest'))->toBe([
        'url' => 'https://example.com/only.jpg',
        'alt' => 'Only image',
    ]);
});

it('returns fallback data when fetch single random image receives an empty batch', function () {
    $service = new class extends TestablePexelsService
    {
        protected function fetchBatch(string $query): array
        {
            return [];
        }
    };

    expect($service->callFetchSingleRandomImage('technology'))
        ->toMatchArray([
            'url' => 'https://example.com/technology.jpg',
            'alt' => 'Technology fallback',
        ]);
});
