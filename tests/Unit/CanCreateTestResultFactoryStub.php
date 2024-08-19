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

namespace Tests\Unit;

use Closure;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultFactory;
use Oru\Harness\Contracts\TestResultState;
use PHPUnit\Framework\MockObject\Stub;
use Throwable;

trait CanCreateTestResultFactoryStub
{
    protected function createTestResultFactoryStub(): TestResultFactory
    {
        return new class($this->createConfiguredStub(...)) implements TestResultFactory
        {
            public function __construct(
                private Closure $createConfiguredStub,
            ) {}

            /**
             * @template TOriginal
             * @param class-string<TOriginal> $originalClassName
             * @param array<string, mixed> $configuration
             * @return Stub&TOriginal
             */
            private function createConfiguredStub(string $originalClassName, array $configuration): Stub
            {
                return ($this->createConfiguredStub)($originalClassName, $configuration);
            }

            public function makeSkipped(string $path): TestResult
            {
                return $this->createConfiguredStub(TestResult::class, ['state' => TestResultState::Skip]);
            }

            /**
             * @param string[] $usedFiles
             */
            public function makeCached(string $path, array $usedFiles): TestResult
            {
                return $this->createConfiguredStub(TestResult::class, ['state' => TestResultState::Cache]);
            }

            /**
             * @param string[] $usedFiles
             */
            public function makeErrored(string $path, array $usedFiles, int $duration, Throwable $throwable): TestResult
            {
                return $this->createConfiguredStub(TestResult::class, ['state' => TestResultState::Error]);
            }

            /**
             * @param string[] $usedFiles
             */
            public function makeFailed(string $path, array $usedFiles, int $duration, Throwable $throwable): TestResult
            {
                return $this->createConfiguredStub(TestResult::class, ['state' => TestResultState::Fail]);
            }

            /**
             * @param string[] $usedFiles
             */
            public function makeSuccessful(string $path, array $usedFiles, int $duration): TestResult
            {
                return $this->createConfiguredStub(TestResult::class, ['state' => TestResultState::Success]);
            }

            public function makeTimedOut(string $path, int $duration): TestResult
            {
                return $this->createConfiguredStub(TestResult::class, ['state' => TestResultState::Timeout]);
            }
        };
    }
}
