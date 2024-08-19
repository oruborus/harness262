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

namespace Oru\Harness\Contracts;

use Oru\Harness\Contracts\TestResult;
use Throwable;

interface TestResultFactory
{
    public function makeSkipped(string $path): TestResult;

    /**
     * @param string[] $usedFiles
     */
    public function makeCached(string $path, array $usedFiles): TestResult;

    /**
     * @param string[] $usedFiles
     */
    public function makeErrored(string $path, array $usedFiles, int $duration, Throwable $throwable): TestResult;

    /**
     * @param string[] $usedFiles
     */
    public function makeFailed(string $path, array $usedFiles, int $duration, Throwable $throwable): TestResult;

    /**
     * @param string[] $usedFiles
     */
    public function makeSuccessful(string $path, array $usedFiles, int $duration): TestResult;

    public function makeTimedOut(string $path, int $duration): TestResult;
}
