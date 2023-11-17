<?php

declare(strict_types=1);

namespace Oru\Harness\Cache;

use Oru\Harness\Contracts\CacheRepository;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestResult;

final readonly class NoCacheRepository implements CacheRepository
{
    public function get(TestConfig $config): null
    {
        return null;
    }

    public function set(TestConfig $config, TestResult $result): void {}
}
