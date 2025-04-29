<?php

/**
 * Copyright (c) 2023-2025, Felix Jahn
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
use Oru\Harness\Contracts\EngineFactory;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\TestResultFactory;
use Oru\Harness\Contracts\TestRunner;
use Oru\Harness\Contracts\TestRunnerFactory;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Contracts\TestSuite;
use Oru\Harness\Loop\TaskLoop;
use Oru\Harness\Subprocess\PhpSubprocessFactory;

final class GenericTestRunnerFactory implements TestRunnerFactory
{
    public function __construct(
        private EngineFactory $engineFactory,
        private AssertionFactory $assertionFactory,
        private Printer $printer,
        private Command $command,
        private CacheRepository $cacheRepository,
        private TestResultFactory $testResultFactory,
    ) {}

    public function make(TestSuite $testSuite): TestRunner
    {
        $testRunner = match ($testSuite->testRunnerMode()) {
            TestRunnerMode::Linear   => new LinearTestRunner($this->engineFactory, $this->assertionFactory, $this->printer, $this->testResultFactory),
            TestRunnerMode::Parallel => new ParallelTestRunner($this->printer, $this->command),
            TestRunnerMode::Async    => new PhpSubprocessTestRunner($this->printer, new TaskLoop($testSuite->concurrency()), new PhpSubprocessFactory($this->command, $this->testResultFactory))
        };

        if (!$testSuite->cache()) {
            return $testRunner;
        }

        if ($testSuite->testRunnerMode() === TestRunnerMode::Linear) {
            return $testRunner;
        }

        return new CacheTestRunner($this->cacheRepository, $testRunner, $this->testResultFactory);
    }
}
