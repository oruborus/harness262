<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface CacheRepository
{
    public function get(TestConfig $config): ?TestResult;

    public function set(TestConfig $config, TestResult $result): void;
}
