<?php

declare(strict_types=1);

namespace Tests\Unit;

use Bugo\PexelsLaravel\Services\PexelsService;
use Devscast\Pexels\Client;
use Devscast\Pexels\Exception\NetworkException;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class PexelsServiceFetchBatchIsolatedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Client::$response = null;
        Client::$capturedQuery = null;
        Client::$capturedParameters = null;
    }

    public function test_fetch_batch_maps_the_pexels_response(): void
    {
        Client::$response = (object) [
            'total_results' => 2,
            'photos' => [
                (object) [
                    'src' => (object) ['original' => 'https://example.com/photo-1.jpg'],
                    'photographer' => 'Alice',
                    'photographer_url' => 'https://pexels.com/@alice',
                    'url' => 'https://pexels.com/photos/1',
                    'alt' => 'Forest path',
                ],
                (object) [
                    'src' => (object) ['original' => 'https://example.com/photo-2.jpg'],
                    'photographer' => 'Bob',
                    'photographer_url' => 'https://pexels.com/@bob',
                    'url' => 'https://pexels.com/photos/2',
                    'alt' => null,
                ],
            ],
        ];

        $service = new class extends PexelsService
        {
            public function callFetchBatch(string $query): array
            {
                return $this->fetchBatch($query);
            }
        };

        self::assertSame([
            [
                'url' => 'https://example.com/photo-1.jpg',
                'photographer' => 'Alice',
                'photographer_url' => 'https://pexels.com/@alice',
                'pexels_url' => 'https://pexels.com/photos/1',
                'alt' => 'Forest path',
                'query' => 'forest',
                'from_api' => true,
            ],
            [
                'url' => 'https://example.com/photo-2.jpg',
                'photographer' => 'Bob',
                'photographer_url' => 'https://pexels.com/@bob',
                'pexels_url' => 'https://pexels.com/photos/2',
                'alt' => 'forest image',
                'query' => 'forest',
                'from_api' => true,
            ],
        ], $service->callFetchBatch('forest'));

        self::assertSame('forest', Client::$capturedQuery);
        self::assertSame(80, Client::$capturedParameters?->per_page);
    }

    public function test_fetch_batch_returns_empty_array_when_response_has_no_results(): void
    {
        Client::$response = (object) [
            'total_results' => 0,
            'photos' => [],
        ];

        $service = new class extends PexelsService
        {
            public function callFetchBatch(string $query): array
            {
                return $this->fetchBatch($query);
            }
        };

        self::assertSame([], $service->callFetchBatch('forest'));
    }

    public function test_fetch_batch_returns_empty_array_on_network_exception(): void
    {
        Client::$response = new NetworkException('Network error');

        $service = new class extends PexelsService
        {
            public function callFetchBatch(string $query): array
            {
                return $this->fetchBatch($query);
            }
        };

        self::assertSame([], $service->callFetchBatch('forest'));
    }
}
