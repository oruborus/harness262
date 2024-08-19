<?php

/**
 * Copyright (c) 2023-2024, Felix Jahn
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

use Fiber;
use Oru\Harness\Contracts\Command;
use Oru\Harness\Contracts\Loop;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Contracts\TestRunner;
use Oru\Harness\Loop\FiberTask;
use Oru\Harness\TestResult\GenericTestResult;
use Oru\Harness\TestRunner\Exception\StopOnCharacteristicMetException;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\PhpSubprocess;
use Throwable;

use function assert;
use function serialize;
use function unserialize;

final class PhpSubprocessTestRunner implements TestRunner
{
    /**
     * @var TestResult[] $results
     */
    private array $results = [];

    public function __construct(
        private readonly Printer $printer,
        private readonly Command $command,
        private readonly Loop $loop
    ) {}

    public function add(TestCase $testCase): void
    {
        $task = new FiberTask(
            new Fiber(fn(): TestResult => $this->runTest($testCase)),
            function (TestResult $testResult) use ($testCase): void {
                $this->results[] = $testResult;
                $this->printer->step($testResult->state());
                if (
                    $testResult->state() === TestResultState::Error
                    && ($testCase->testSuite()->stopOnCharacteristic() === StopOnCharacteristic::Error
                        || $testCase->testSuite()->stopOnCharacteristic() === StopOnCharacteristic::Defect)
                ) {
                    throw new StopOnCharacteristicMetException();
                }
                if (
                    $testResult->state() === TestResultState::Fail
                    && ($testCase->testSuite()->stopOnCharacteristic() === StopOnCharacteristic::Failure
                        || $testCase->testSuite()->stopOnCharacteristic() === StopOnCharacteristic::Defect)
                ) {
                    throw new StopOnCharacteristicMetException();
                }
            },
            static function (Throwable $throwable): never {
                throw $throwable;
            }
        );

        $this->loop->add($task);
    }

    /**
     * @return TestResult[]
     */
    public function run(): array
    {
        try {
            $this->loop->run();
        } catch (StopOnCharacteristicMetException) {
        }

        return $this->results;
    }

    /**
     * @return TestResult[]
     */
    public function results(): array
    {
        return $this->results;
    }

    /**
     * @throws RuntimeException
     * @throws Throwable
     */
    private function runTest(TestCase $testCase): TestResult
    {
        $process = new Process($this->command, $testCase, $testCase->testSuite()->timeout());
        $process->start();

        while ($process->isRunning()) {
            Fiber::suspend();
        }

        return $process->result();
    }
}

/** @internal */
final class Process
{
    private readonly PhpSubprocess $phpSubprocess;

    private bool $timedOut = false;

    public function __construct(
        Command $command,
        private readonly TestCase $testCase,
        private readonly int $timeout,
    ) {
        $phpSubprocess = new PhpSubprocess([(string) $command]);

        $phpSubprocess->setInput(serialize($testCase));

        $phpSubprocess->setTimeout($timeout);

        $this->phpSubprocess = $phpSubprocess;
    }

    public function start(): void
    {
        $this->phpSubprocess->start();
    }

    public function isRunning(): bool
    {
        return $this->phpSubprocess->isRunning() && !$this->timedOut();
    }

    public function timedOut(): bool
    {
        if ($this->timedOut) {
            return $this->timedOut;
        }

        try {
            $this->phpSubprocess->checkTimeout();
        } catch (ProcessTimedOutException) {
            return ($this->timedOut = true);
        }

        return false;
    }

    public function result(): TestResult
    {
        if ($this->timedOut) {
            return new GenericTestResult(TestResultState::Timeout, $this->testCase->path(), [], $this->timeout);
        }

        // TODO: Add subprocess error handling
        assert($this->phpSubprocess->getExitCode() === 0, $this->phpSubprocess->getOutput());

        $result = unserialize($this->phpSubprocess->getOutput());

        if ($result instanceof Throwable) {
            throw $result;
        }

        if (!$result instanceof TestResult) {
            // TODO: Throw a distinct exception describing the concrete nature
            throw new RuntimeException("Subprocess did not return a `TestResult` - Returned: {$this->phpSubprocess->getOutput()}");
        }

        return $result;
    }
}
