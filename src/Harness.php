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

namespace Oru\Harness;

use Oru\Harness\Contracts\FilterFactory;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\TestCaseFactory;
use Oru\Harness\Contracts\TestRunnerFactory;
use Oru\Harness\Contracts\TestSuiteFactory;
use Oru\Harness\Filter\Exception\MalformedRegularExpressionPatternException;
use Oru\Harness\Frontmatter\Exception\MissingRequiredKeyException;
use Oru\Harness\Frontmatter\Exception\ParseException;
use Oru\Harness\Frontmatter\Exception\UnrecognizedKeyException;
use Oru\Harness\Frontmatter\Exception\UnrecognizedNegativePhaseException;
use Oru\Harness\TestCase\Exception\MissingFrontmatterException;
use Oru\Harness\TestSuite\Exception\InvalidPathException;
use Oru\Harness\TestSuite\Exception\MissingPathException;

use function count;
use function time;

final readonly class Harness
{
    public function __construct(
        private FilterFactory $filterFactory,
        private Printer $printer,
        private TestCaseFactory $testCaseFactory,
        private TestRunnerFactory $testRunnerFactory,
        private TestSuiteFactory $testSuiteFactory,
    ) {}

    /**
     * @throws MissingFrontmatterException
     * @throws MissingRequiredKeyException
     * @throws UnrecognizedKeyException
     * @throws UnrecognizedNegativePhaseException
     * @throws ParseException
     */
    public function run(): int
    {
        // 1. Let testSuiteStartTime be the current system time in seconds.
        $testSuiteStartTime = time();

        // 2. Perform **printer**.start().
        $this->printer->start();

        try {
            $testSuite = $this->testSuiteFactory->make();
        } catch (InvalidPathException $exception) {
            $this->printer->writeLn($exception->getMessage());
            return 1;
        } catch (MissingPathException $exception) {
            // TODO: Print command usage here.
            $this->printer->writeLn($exception->getMessage());
            return 1;
        }

        $testRunner = $this->testRunnerFactory->make($testSuite);

        try {
            $filter = $this->filterFactory->make();
        } catch (MalformedRegularExpressionPatternException $exception) {
            $this->printer->writeLn('The provided regular expression pattern is malformed.');
            $this->printer->writeLn('The following warning was issued:');
            $this->printer->writeLn("\"{$exception->getMessage()}\"");
            return 1;
        }

        // 3. Let **preparedTestCases** be the result of **testCaseFactory**.make() for every element of **testSuite**.[[paths]].
        $preparedTestCases = $this->testCaseFactory->make($testSuite, $testSuite->paths());

        // 4. Let **filteredTestCases** be the result of **filter**.apply() for every element of **preparedTestCases**.
        $filteredTestCases = $filter->apply(...$preparedTestCases);

        // 5. Perform **printer**.setStepCount(count(**preparedTestCases**)).
        $this->printer->setStepCount(count($filteredTestCases));

        // 6. For each **testCase** of **filteredTestCases**, do
        foreach ($filteredTestCases as $testCase) {
            // a. Perform **testRunner**.add(**testCase**).
            $testRunner->add($testCase);
        }

        // 7. Let **testSuiteEndTime** be the current system time in seconds.
        $testSuiteEndTime = time();

        // 8. Perform **printer**.end(**testRunner**.run(), **testSuiteEndTime** - **testSuiteStartTime**).
        $this->printer->end($testRunner->run(), $testSuiteEndTime - $testSuiteStartTime);

        return 0;
    }
}
