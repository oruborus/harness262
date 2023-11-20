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

namespace Oru\Harness\TestRunner;

use Oru\Harness\Contracts\AssertionFactory;
use Oru\Harness\Contracts\CacheRepository;
use Oru\Harness\Contracts\Command;
use Oru\Harness\Contracts\Facade;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\TestRunner;
use Oru\Harness\Contracts\TestRunnerFactory;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Contracts\TestSuiteConfig;
use Oru\Harness\Loop\TaskLoop;

final class GenericTestRunnerFactory implements TestRunnerFactory
{
    public function __construct(
        private Facade $facade,
        private AssertionFactory $assertionFactory,
        private Printer $printer,
        private Command $command,
        private CacheRepository $cacheRepository
    ) {}

    public function make(TestSuiteConfig $config): TestRunner
    {
        $testRunner = match ($config->testRunnerMode()) {
            TestRunnerMode::Linear   => new LinearTestRunner($this->facade, $this->assertionFactory, $this->printer),
            TestRunnerMode::Parallel => new ParallelTestRunner($this->assertionFactory, $this->printer, $this->command),
            TestRunnerMode::Async    => new AsyncTestRunner($this->printer, $this->command, new TaskLoop($config->concurrency()))
        };

        if (!$config->cache()) {
            return $testRunner;
        }

        if ($config->testRunnerMode() === TestRunnerMode::Linear) {
            return $testRunner;
        }

        return new CacheTestRunner($this->cacheRepository, $testRunner);
    }
}
