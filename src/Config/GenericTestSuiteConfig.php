<?php

declare(strict_types=1);

namespace Oru\Harness\Config;

use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Contracts\TestSuiteConfig;

final class GenericTestSuiteConfig implements TestSuiteConfig
{
    public function __construct(
        /**
         * @var string[] $paths
         */
        private array $paths,
        private bool $cache,
        private int $concurrency,
        private TestRunnerMode $testRunnerMode,
        private StopOnCharacteristic $stopOnCharacteristic,
    ) {}

    /**
     * @return string[]
     */
    public function paths(): array
    {
        return $this->paths;
    }

    public function cache(): bool
    {
        return $this->cache;
    }

    public function testRunnerMode(): TestRunnerMode
    {
        return $this->testRunnerMode;
    }

    public function concurrency(): int
    {
        return $this->concurrency;
    }

    public function StopOnCharacteristic(): StopOnCharacteristic
    {
        return $this->stopOnCharacteristic;
    }
}
