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

namespace Oru\Harness\TestResult;

use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultFactory;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\TestResult\GenericTestResult;
use Throwable;

final class GenericTestResultFactory implements TestResultFactory
{
    public function makeSkipped(string $path): TestResult
    {
        return new GenericTestResult(TestResultState::Skip, $path, [], 0);
    }

    /**
     * @param string[] $usedFiles
     */
    public function makeCached(string $path, array $usedFiles): TestResult
    {
        return new GenericTestResult(TestResultState::Cache, $path, $usedFiles, 0);
    }

    /**
     * @param string[] $usedFiles
     */
    public function makeErrored(string $path, array $usedFiles, int $duration, Throwable $throwable): TestResult
    {
        return new GenericTestResult(TestResultState::Error, $path, $usedFiles, $duration, $throwable);
    }

    /**
     * @param string[] $usedFiles
     */
    public function makeFailed(string $path, array $usedFiles, int $duration, Throwable $throwable): TestResult
    {
        return new GenericTestResult(TestResultState::Fail, $path, $usedFiles, $duration, $throwable);
    }

    /**
     * @param string[] $usedFiles
     */
    public function makeSuccessful(string $path, array $usedFiles, int $duration): TestResult
    {
        return new GenericTestResult(TestResultState::Success, $path, $usedFiles, $duration);
    }

    public function makeTimedOut(string $path, int $duration): TestResult
    {
        return new GenericTestResult(TestResultState::Timeout, $path, [], $duration);
    }
}
