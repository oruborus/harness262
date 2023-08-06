<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface CacheRepository
{
    /**
     * @throws FileNotFound
     */
    public function get(TestConfig $config): ?TestResult;

    /**
     * @throws FileWriteFailed
     */
    public function set(TestConfig $config, TestResult $result): void;
}
