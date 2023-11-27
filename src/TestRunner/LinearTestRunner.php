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

use Oru\Harness\Assertion\Exception\AssertionFailedException;
use Oru\Harness\Contracts\AssertionFactory;
use Oru\Harness\Contracts\Facade;
use Oru\Harness\Contracts\FrontmatterFlag;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Contracts\TestRunner;
use Throwable;

use function array_diff;
use function count;
use function in_array;
use function ob_get_clean;
use function ob_start;

final class LinearTestRunner implements TestRunner
{
    /**
     * @var TestCase[] $testCases
     */
    private array $testCases = [];

    /**
     * @var TestResult[] $results
     */
    private array $results = [];

    public function __construct(
        private readonly Facade $facade,
        private readonly AssertionFactory $assertionFactory,
        private readonly Printer $printer
    ) {}

    public function add(TestCase $testCase): void
    {
        $this->testCases[] = $testCase;
    }

    private function runTest(TestCase $testCase): mixed
    {
        if (!in_array(FrontmatterFlag::async, $testCase->frontmatter()->flags())) {
            return $this->facade->engineRun();
        }

        ob_start();
        /**
         * @psalm-suppress MixedAssignment  Engine intentionally returns `mixed`
         */
        $returnValue = $this->facade->engineRun();
        $output = ob_get_clean();

        return $this->facade->isNormalCompletion($returnValue) ?
            $output :
            $returnValue;
    }

    private function addResult(TestResult $result): void
    {
        $this->results[] = $result;
        $this->printer->step($result->state());
    }

    /**
     * @return TestResult[]
     */
    public function run(): array
    {
        foreach ($this->testCases as $testCase) {

            $differences = array_diff($testCase->frontmatter()->features(), $this->facade->engineSupportedFeatures());

            if (count($differences) > 0) {
                $this->addResult(new GenericTestResult(TestResultState::Skip, $testCase->path(), [], 0));
                continue;
            }

            $this->facade->initialize();

            foreach ($testCase->frontmatter()->includes() as $include) {
                $this->facade->engineAddFiles($include->value);
            }

            $this->facade->engineAddCode($testCase->content());

            try {
                /**
                 * @psalm-suppress MixedAssignment  Test outcomes intentionally return `mixed`
                 */
                $actual = $this->runTest($testCase);
            } catch (Throwable $throwable) {
                $this->addResult(new GenericTestResult(TestResultState::Error, $testCase->path(), [], 0, $throwable));
                if (
                    $testCase->testSuite()->stopOnCharacteristic() === StopOnCharacteristic::Error
                    || $testCase->testSuite()->stopOnCharacteristic() === StopOnCharacteristic::Defect
                ) {
                    break;
                }
                continue;
            }

            $assertion = $this->assertionFactory->make($testCase);

            try {
                /**
                 * @psalm-suppress PossiblyUndefinedVariable  `$actual` is never undefined as the previous catch-block either continues or breaks
                 */
                $assertion->assert($actual);
            } catch (AssertionFailedException $assertionFailedException) {
                $this->addResult(new GenericTestResult(TestResultState::Fail, $testCase->path(), [], 0, $assertionFailedException));
                if (
                    $testCase->testSuite()->stopOnCharacteristic() === StopOnCharacteristic::Failure
                    || $testCase->testSuite()->stopOnCharacteristic() === StopOnCharacteristic::Defect
                ) {
                    break;
                }
                continue;
            } catch (Throwable $throwable) {
                $this->addResult(new GenericTestResult(TestResultState::Error, $testCase->path(), [], 0, $throwable));
                if (
                    $testCase->testSuite()->stopOnCharacteristic() === StopOnCharacteristic::Error
                    || $testCase->testSuite()->stopOnCharacteristic() === StopOnCharacteristic::Defect
                ) {
                    break;
                }
                continue;
            }

            $this->addResult(new GenericTestResult(TestResultState::Success, $testCase->path(), [], 0));
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
}
