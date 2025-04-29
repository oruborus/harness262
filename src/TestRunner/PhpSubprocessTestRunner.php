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

use Fiber;
use Oru\Harness\Contracts\Loop;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\SubprocessFactory;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Contracts\TestRunner;
use Oru\Harness\Loop\FiberTask;
use Oru\Harness\TestRunner\Exception\StopOnCharacteristicMetException;
use Throwable;

final class PhpSubprocessTestRunner implements TestRunner
{
    /** @var TestResult[] $results */
    private array $results = [];

    public function __construct(
        private readonly Printer $printer,
        private readonly Loop $loop,
        private readonly SubprocessFactory $subprocessFactory,
    ) {}

    public function add(TestCase $testCase): void
    {
        $task = new FiberTask(
            new Fiber(fn(): TestResult => $this->subprocessFactory->make($testCase)->run()),
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

    /** @return TestResult[] */
    public function run(): array
    {
        try {
            $this->loop->run();
        } catch (StopOnCharacteristicMetException) {
        }

        return $this->results;
    }

    /** @return TestResult[] */
    public function results(): array
    {
        return $this->results;
    }
}
