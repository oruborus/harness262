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

use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\EcmaScript\Core\Contracts\Values\AbruptCompletion;
use Oru\Harness\Assertion\Exception\AssertionFailedException;
use Oru\Harness\Contracts\AssertionFactory;
use Oru\Harness\Contracts\EngineFactory;
use Oru\Harness\Contracts\FrontmatterFlag;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultFactory;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Contracts\TestRunner;
use Oru\Harness\Helpers\OutputBuffer;
use Throwable;

use function array_diff;
use function in_array;

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
        private readonly EngineFactory $engineFactory,
        private readonly AssertionFactory $assertionFactory,
        private readonly Printer $printer,
        private readonly TestResultFactory $testResultFactory,
    ) {}

    public function add(TestCase $testCase): void
    {
        $this->testCases[] = $testCase;
    }

    /**
     * @return TestResult[]
     */
    public function run(): array
    {
        foreach ($this->testCases as $testCase) {
            $engine = $this->engineFactory->make();

            $testResult = $this->runTestCase($engine, $testCase);

            $this->results[] = $testResult;
            $this->printer->step($testResult->state());

            if (match ([$testCase->testSuite()->stopOnCharacteristic(), $testResult->state()]) {
                [StopOnCharacteristic::Error,   TestResultState::Error],
                [StopOnCharacteristic::Defect,  TestResultState::Error],
                [StopOnCharacteristic::Failure, TestResultState::Fail],
                [StopOnCharacteristic::Defect,  TestResultState::Fail] => true,
                default => false
            }) {
                break;
            }
        }

        return $this->results;
    }

    private function hasUnsupportedFeatures(Engine $engine, TestCase $testCase): bool
    {
        return (bool) array_diff($testCase->frontmatter()->features(), $engine->getSupportedFeatures());
    }

    private function runTestCase(Engine $engine, TestCase $testCase): TestResult
    {
        if ($this->hasUnsupportedFeatures($engine, $testCase)) {
            return $this->testResultFactory->makeSkipped($testCase->path());
        }

        foreach ($testCase->frontmatter()->includes() as $include) {
            $engine->addFiles($include->value);
        }

        $engine->addCode($testCase->content());

        $assertion = $this->assertionFactory->make($testCase);

        try {
            $actual = $this->runTestCodeInEngine($engine, $testCase);
            $assertion->assert($actual);
            return $this->testResultFactory->makeSuccessful($testCase->path(), [], 0);
        } catch (AssertionFailedException $assertionFailedException) {
            return $this->testResultFactory->makeFailed($testCase->path(), [], 0, $assertionFailedException);
        } catch (Throwable $throwable) {
            return $this->testResultFactory->makeErrored($testCase->path(), [], 0, $throwable);
        }
    }

    private function runTestCodeInEngine(Engine $engine, TestCase $testCase): mixed
    {
        $outputBuffer = new OutputBuffer();

        $returnValue = $engine->run();

        if (
            in_array(FrontmatterFlag::async, $testCase->frontmatter()->flags())
            && !$returnValue instanceof AbruptCompletion
        ) {
            return (string) $outputBuffer;
        }

        return $returnValue;
    }

    /**
     * @return TestResult[]
     */
    public function results(): array
    {
        return $this->results;
    }
}
