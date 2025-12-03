<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pexels API Key
    |--------------------------------------------------------------------------
    |
    | Your Pexels API key. Get one at https://www.pexels.com/api/key/
    |
    */
    'api_key' => env('PEXELS_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limits
    |--------------------------------------------------------------------------
    |
    | Configure your Pexels API rate limits
    |
    */
    'rate_limits' => [
        'hourly' => env('PEXELS_HOURLY_LIMIT', 200),
        'monthly' => env('PEXELS_MONTHLY_LIMIT', 20000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Enable/disable caching for API responses
    |
    */
    'cache' => [
        'enabled' => env('PEXELS_CACHE_ENABLED', true),
        'ttl' => env('PEXELS_CACHE_TTL', 3600), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Images
    |--------------------------------------------------------------------------
    |
    | Default images to use when API limits are exceeded
    |
    */
    'fallback_images' => [
        'nature' => [
            'url' => 'https://images.pexels.com/photos/414612/pexels-photo-414612.jpeg',
            'photographer' => 'Pexels',
            'photographer_url' => 'https://www.pexels.com/@pexels',
            'pexels_url' => 'https://www.pexels.com/photo/414612/',
            'alt' => 'Beautiful nature landscape',
        ],
        'technology' => [
            'url' => 'https://images.pexels.com/photos/177598/pexels-photo-177598.jpeg',
            'photographer' => 'Pexels',
            'photographer_url' => 'https://www.pexels.com/@pexels',
            'pexels_url' => 'https://www.pexels.com/photo/177598/',
            'alt' => 'Technology concept',
        ],
        'food' => [
            'url' => 'https://images.pexels.com/photos/376464/pexels-photo-376464.jpeg',
            'photographer' => 'Pexels',
            'photographer_url' => 'https://www.pexels.com/@pexels',
            'pexels_url' => 'https://www.pexels.com/photo/376464/',
            'alt' => 'Delicious food',
        ],
    ],
];
