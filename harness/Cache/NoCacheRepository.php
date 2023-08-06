<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Cache;

use Oru\EcmaScript\Harness\Contracts\CacheRepository;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestResult;

final readonly class NoCacheRepository implements CacheRepository
{
    public function get(TestConfig $config): null
    {
        return null;
    }

    public function set(TestConfig $config, TestResult $result): void
    {
    }
}
