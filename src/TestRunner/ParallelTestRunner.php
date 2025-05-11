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
use Oru\Harness\Contracts\Command;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestRunner;
use Oru\Harness\Helpers\Serializer;
use RuntimeException;
use Throwable;

use function assert;
use function fclose;
use function fwrite;
use function proc_close;
use function proc_open;
use function stream_get_contents;

final class ParallelTestRunner implements TestRunner
{
    private bool $dirty = true;

    /** @var TestCase[] $testCases */
    private array $testCases = [];

    /** @var TestResult[] $results */
    private array $results = [];

    public function __construct(
        private readonly Printer $printer,
        private readonly Command $command,
        private Serializer $serializer = new Serializer(),
    ) {}

    public function add(TestCase $testCase): void
    {
        $this->dirty = true;
        $this->testCases[] = $testCase;
    }

    /** @return TestResult[] */
    public function run(): array
    {
        if (!$this->dirty) {
            return $this->results;
        }

        foreach ($this->testCases as $testCase) {
            $this->dirty = false;
            $serializedConfig = $this->serializer->serialize($testCase);

            $descriptorspec = [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["pipe", "w"]
            ];

            $cwd = '.';
            $env = [];

            $options = ['bypass_shell' => true];

            /** @var array{0: resource, 1: resource, 2: resource} $pipes */
            $process = @proc_open((string) $this->command, $descriptorspec, $pipes, $cwd, $env, $options)
                ?: throw new RuntimeException('Could not open process');

            fwrite($pipes[0], $serializedConfig);
            fclose($pipes[0]);

            if (Fiber::getCurrent()) {
                while (proc_get_status($process)['running']) {
                    Fiber::suspend();
                }
            }

            $output = stream_get_contents($pipes[1]) ?: '';
            fclose($pipes[1]);
            fclose($pipes[2]);

            $exitCode = proc_close($process);
            assert($exitCode === 0, $output);

            $result = $this->serializer->unserialize($output);

            if ($result instanceof Throwable) {
                throw $result;
            }

            if (!$result instanceof TestResult) {
                throw new RuntimeException("Subprocess did not return a `TestResult` - Returned: {$output}");
            }

            $this->printer->step($result->state());
            $this->results[] = $result;
        }

        return $this->results;
    }

    /** @return TestResult[] */
    public function results(): array
    {
        return $this->results;
    }
}
