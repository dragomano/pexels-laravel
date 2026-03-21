# Pexels Laravel

A Laravel package for integrating Pexels API with built-in rate limiting, caching, and fallback images.

## Features

- Easy integration with Pexels API
- Built-in rate limiting (hourly and monthly)
- Configurable caching
- Automatic fallback images when limits are exceeded
- Usage statistics command

## Requirements

- PHP 8.2+
- Laravel 11+

## Installation

### 1. Install via Composer

```bash
composer require bugo/pexels-laravel
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=pexels-config
```

This will create `config/pexels.php` in your application.

### 3. Add API Key to `.env`

```env
PEXELS_API_KEY=your_api_key
```

Get your free API key at [https://www.pexels.com/api/key/](https://www.pexels.com/api/key/)

## Configuration

The `config/pexels.php` file contains all configuration options:

```php
return [
    // Your Pexels API key
    'api_key' => env('PEXELS_API_KEY'),

    // Rate limits (adjust based on your Pexels plan)
    'rate_limits' => [
        'hourly' => env('PEXELS_HOURLY_LIMIT', 200),
        'monthly' => env('PEXELS_MONTHLY_LIMIT', 20000),
    ],

    // Cache settings
    'cache' => [
        'enabled' => env('PEXELS_CACHE_ENABLED', true),
        'ttl' => env('PEXELS_CACHE_TTL', 3600), // seconds
    ],

    // Fallback images when API limits are exceeded
    'fallback_images' => [
        'nature' => [...],
        'technology' => [...],
        'food' => [...],
    ],
];
```

### Environment Variables

```env
# Required
PEXELS_API_KEY=your_api_key

# Optional (with defaults)
PEXELS_CACHE_ENABLED=true
PEXELS_CACHE_TTL=3600
PEXELS_HOURLY_LIMIT=200
PEXELS_MONTHLY_LIMIT=20000
```

## Usage

### Basic Usage

```php
use Bugo\PexelsLaravel\Services\PexelsService;

$pexelsService = app(PexelsService::class);

// Get a random image by search query
$image = $pexelsService->getRandomImage('nature');
```

### Response Format

It returns an array with the following structure:

```php
[
    'url' => 'https://images.pexels.com/photos/...',
    'photographer' => 'John Doe',
    'photographer_url' => 'https://www.pexels.com/@johndoe',
    'pexels_url' => 'https://www.pexels.com/photo/...',
    'alt' => 'Image description',
    'query' => 'nature',
    'from_api' => true, // false if fallback image
]
```

### In Controllers

```php
namespace App\Http\Controllers;

use Bugo\PexelsLaravel\Services\PexelsService;

class ImageController extends Controller
{
    public function random(PexelsService $pexelsService)
    {
        $image = $pexelsService->getRandomImage('technology');

        return view('image', ['image' => $image]);
    }
}
```

### In Blade Templates

```blade
<img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}">

@if(!empty($image['photographer']))
<p class="text-sm text-gray-600">
    Photo by
    <a href="{{ $image['photographer_url'] }}" target="_blank">
        {{ $image['photographer'] }}
    </a>
    on
    <a href="{{ $image['pexels_url'] }}" target="_blank">Pexels</a>
</p>
@endif
```

### In Database Seeders

```php
use Bugo\PexelsLaravel\Services\PexelsService;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $pexelsService = app(PexelsService::class);

        $queries = ['nature', 'technology', 'business', 'travel'];

        for ($i = 0; $i < 50; $i++) {
            $query = $queries[array_rand($queries)];
            $image = $pexelsService->getRandomImage($query);

            Article::create([
                'title' => fake()->sentence,
                'content' => fake()->paragraphs(3, true),
                'image_url' => $image['url'],
                'image_alt' => $image['alt'],
                'image_photographer' => $image['photographer'],
                'image_photographer_url' => $image['photographer_url'],
            ]);
        }
    }
}
```

### Usage Statistics

Check your current API usage:

```bash
php artisan pexels:usage
```

Output example:
```
Pexels API Usage Statistics

+---------+------+-------+-----------+------------+
| Period  | Used | Limit | Remaining | Percentage |
+---------+------+-------+-----------+------------+
| Hourly  | 45   | 180   | 135       | 25%        |
| Monthly | 3420 | 19000 | 15580     | 18%        |
+---------+------+-------+-----------+------------+
```
