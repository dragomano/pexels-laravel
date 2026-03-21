<?php

declare(strict_types=1);

require_once __DIR__ . '/Fakes/PexelsClientFake.php';

use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');
