<?php

declare(strict_types=1);

namespace Devscast\Pexels;

use Devscast\Pexels\Parameter\SearchParameters;
use Throwable;

if (! class_exists(Client::class, false)) {
    class Client
    {
        public static mixed $response = null;

        public static ?string $capturedQuery = null;

        public static ?SearchParameters $capturedParameters = null;

        public function __construct(string $token, ?string $proxy = null) {}

        public function searchPhotos(string $query, SearchParameters $parameters = new SearchParameters()): object
        {
            self::$capturedQuery = $query;
            self::$capturedParameters = $parameters;

            if (self::$response instanceof Throwable) {
                throw self::$response;
            }

            return self::$response;
        }
    }
}
