<?php

/**
 * Copyright (c) 2023, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Oru\Harness\Config;

use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Contracts\TestSuite;

final class GenericTestSuite implements TestSuite
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
