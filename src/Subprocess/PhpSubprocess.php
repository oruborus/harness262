<?php

/**
 * Copyright (c) 2024, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Oru\Harness\Subprocess;

use Fiber;
use Oru\Harness\Contracts\Subprocess;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultFactory;
use Oru\Harness\Helpers\ErrorHandler;
use Oru\Harness\Subprocess\Exception\InvalidReturnValueException;
use Oru\Harness\Subprocess\Exception\ProcessAlreadyRunningException;
use Oru\Harness\Subprocess\Exception\ProcessFailureException;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\PhpSubprocess as SymfonyPhpSubprocess;
use Throwable;

use function serialize;
use function unserialize;

use const E_WARNING;

final class PhpSubprocess implements Subprocess
{
    private bool $timedOut = false;

    public function __construct(
        private readonly SymfonyPhpSubprocess $phpSubprocess,
        private readonly TestCase $testCase,
        private readonly TestResultFactory $testResultFactory,
    ) {
        // TODO: Handle serialization exceptions
        $serializedTestCase = serialize($testCase);

        /** @psalm-suppress MissingThrowsDocblock Process is not running when input is set */
        $phpSubprocess->setInput($serializedTestCase);

        /** @psalm-suppress MissingThrowsDocblock Timeout is non-negative-int */
        $phpSubprocess->setTimeout($testCase->testSuite()->timeout());
    }

    /** 
     * @throws InvalidReturnValueException when the subprocess does not return a TestResult object
     * @throws ProcessFailureException when the process failed to start
     * @throws ProcessAlreadyRunningException when the process was started again
     * @throws Throwable
     */
    public function run(): TestResult
    {
        try {
            /** @psalm-suppress MissingThrowsDocblock No callback is provided */
            $this->phpSubprocess->start();
        } catch (ProcessStartFailedException $e) {
            throw new ProcessFailureException($e->getMessage(), $e->getCode(), $e);
        } catch (RuntimeException $e) {
            throw new ProcessAlreadyRunningException($e->getMessage(), $e->getCode(), $e);
        }

        while ($this->phpSubprocess->isRunning() && !$this->timedOut()) {
            Fiber::suspend();
        }

        if ($this->timedOut) {
            return $this->testResultFactory->makeTimedOut($this->testCase->path(), $this->testCase->testSuite()->timeout());
        }

        /** @psalm-suppress MissingThrowsDocblock */
        $output = $this->phpSubprocess->getOutput();

        // TODO: Add subprocess error handling
        assert($this->phpSubprocess->getExitCode() === 0, "ASSERTION FAILED: {$output}");

        $_ = new ErrorHandler(
            fn(): never => throw new InvalidReturnValueException("Subprocess did not return a `TestResult` - Returned: {$output}"),
            E_WARNING
        );
        $result = unserialize($output);

        if ($result instanceof Throwable) {
            throw $result;
        }

        if (!$result instanceof TestResult) {
            throw new InvalidReturnValueException("Subprocess did not return a `TestResult` - Returned: {$output}");
        }

        return $result;
    }

    private function timedOut(): bool
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
}
