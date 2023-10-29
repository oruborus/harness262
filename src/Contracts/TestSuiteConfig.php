<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface TestSuiteConfig extends Config
{
    /**
     * @return string[]
     */
    public function paths(): array;

    public function cache(): bool;

    public function testRunnerMode(): TestRunnerMode;

    public function concurrency(): int;
}
