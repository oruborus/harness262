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

use Fiber;
use Oru\Harness\Contracts\Command;
use Oru\Harness\Contracts\Loop;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Contracts\TestRunner;
use Oru\Harness\Loop\FiberTask;
use Oru\Harness\TestRunner\Exception\StopOnCharacteristicMetException;
use RuntimeException;
use Throwable;

final class AsyncTestRunner implements TestRunner
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

    public function add(TestConfig $config): void
    {
        $task = new FiberTask(
            new Fiber(fn(): TestResult => $this->runTest($config)),
            function (TestResult $testResult) use ($config): void {
                $this->results[] = $testResult;
                $this->printer->step($testResult->state());
                if (
                    $testResult->state() === TestResultState::Error
                    && ($config->testSuiteConfig()->stopOnCharacteristic() === StopOnCharacteristic::Error
                        || $config->testSuiteConfig()->stopOnCharacteristic() === StopOnCharacteristic::Defect)
                ) {
                    throw new StopOnCharacteristicMetException();
                }
                if (
                    $testResult->state() === TestResultState::Fail
                    && ($config->testSuiteConfig()->stopOnCharacteristic() === StopOnCharacteristic::Failure
                        || $config->testSuiteConfig()->stopOnCharacteristic() === StopOnCharacteristic::Defect)
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
    private function runTest(TestConfig $config): TestResult
    {
        $serializedConfig = serialize($config);

        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $cwd = '.';
        $env = [];

        $options = ['bypass_shell' => true];

        $process = @proc_open((string) $this->command, $descriptorspec, $pipes, $cwd, $env, $options)
            ?: throw new RuntimeException('Could not open process');

        fwrite($pipes[0], $serializedConfig);
        fclose($pipes[0]);

        if (Fiber::getCurrent()) {
            while (proc_get_status($process)['running']) {
                Fiber::suspend();
            }
        }

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        assert($exitCode === 0, $output);

        $result = unserialize($output);

        if ($result instanceof Throwable) {
            throw $result;
        }

        if (!$result instanceof TestResult) {
            throw new RuntimeException("Subprocess did not return a `TestResult` - Returned: {$output}");
        }

        return $result;
    }
}
