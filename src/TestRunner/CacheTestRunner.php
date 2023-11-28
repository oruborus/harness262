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

use Oru\Harness\Contracts\CacheRepository;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultFactory;
use Oru\Harness\Contracts\TestRunner;

final class CacheTestRunner implements TestRunner
{
    /**
     * @var TestResult[] $results
     */
    private array $results = [];

    public function __construct(
        private CacheRepository $cacheRepository,
        private TestRunner $testRunner,
        private TestResultFactory $testResultFactory,
    ) {}

    public function add(TestCase $testCase): void
    {
        if ($testResult = $this->cacheRepository->get($testCase)) {
            $this->results[] = $this->testResultFactory->makeCached($testCase->path(), $testResult->usedFiles());
            return;
        }

        $this->testRunner->add($testCase);
    }

    /**
     * @return TestResult[]
     */
    public function run(): array
    {
        return [...$this->results, ...$this->testRunner->run()];
    }

    /**
     * @return TestResult[]
     */
    public function results(): array
    {
        return $this->results;
    }
}
