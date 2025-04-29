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

use Oru\Harness\Assertion\GenericAssertionFactory;
use Oru\Harness\Cache\GenericCacheRepositoryFactory;
use Oru\Harness\Command\FileCommand;
use Oru\Harness\Contracts\ArgumentsParser;
use Oru\Harness\Contracts\EngineFactory;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Filter\Exception\MalformedRegularExpressionPatternException;
use Oru\Harness\Filter\GenericFilterFactory;
use Oru\Harness\Frontmatter\Exception\MissingRequiredKeyException;
use Oru\Harness\Frontmatter\Exception\ParseException;
use Oru\Harness\Frontmatter\Exception\UnrecognizedKeyException;
use Oru\Harness\Frontmatter\Exception\UnrecognizedNegativePhaseException;
use Oru\Harness\Helpers\LogicalCoreCounter;
use Oru\Harness\Helpers\TemporaryFileHandler;
use Oru\Harness\Storage\FileStorage;
use Oru\Harness\TestCase\Exception\MissingFrontmatterException;
use Oru\Harness\TestCase\GenericTestCaseFactory;
use Oru\Harness\TestResult\GenericTestResultFactory;
use Oru\Harness\TestRunner\GenericTestRunnerFactory;
use Oru\Harness\TestSuite\Exception\InvalidPathException;
use Oru\Harness\TestSuite\Exception\MissingPathException;
use Oru\Harness\TestSuite\TestSuiteFactory;

use function count;
use function file_get_contents;
use function realpath;
use function time;

final readonly class Harness
{
    private const TEMPLATE_PATH     = __DIR__ . '/Template/ExecuteTest';
    private const TEST_STORAGE_PATH = '.';

    private TemporaryFileHandler $temporaryFileHandler;

    public function __construct(
        private EngineFactory $engineFactory,
        private ArgumentsParser $argumentsParser,
        private Printer $printer,
    ) {
        $contents = str_replace(
            '{{CONFIG_PATH}}',
            $engineFactory->path(),
            (string) file_get_contents((string) realpath(static::TEMPLATE_PATH))
        );
        $this->temporaryFileHandler = new TemporaryFileHandler($contents);
    }

    /**
     * @throws MissingFrontmatterException
     * @throws MissingRequiredKeyException
     * @throws UnrecognizedKeyException
     * @throws UnrecognizedNegativePhaseException
     * @throws ParseException
     */
    public function run(): int
    {
        $testStorage            = new FileStorage(static::TEST_STORAGE_PATH);
        $assertionFactory       = new GenericAssertionFactory($this->engineFactory);
        $command                = new FileCommand((string) realpath($this->temporaryFileHandler->path()));

        $coreCounter            = new LogicalCoreCounter();

        // 1. Let testSuiteStartTime be the current system time in seconds.
        $testSuiteStartTime = time();

        // 2. Perform **printer**.start().
        $this->printer->start();

        try {
            $testSuiteFactory = new TestSuiteFactory($this->argumentsParser, $coreCounter, $this->printer);
            $testSuite        = $testSuiteFactory->make();
        } catch (InvalidPathException $exception) {
            $this->printer->writeLn($exception->getMessage());
            return 1;
        } catch (MissingPathException $exception) {
            // TODO: Print command usage here.
            $this->printer->writeLn($exception->getMessage());
            return 1;
        }

        $testCaseFactory        = new GenericTestCaseFactory($testStorage, $testSuite);
        $cacheRepositoryFactory = new GenericCacheRepositoryFactory();
        $cacheRepository        = $cacheRepositoryFactory->make($testSuite);

        $testResultFactory      = new GenericTestResultFactory();
        $testRunnerFactory      = new GenericTestRunnerFactory($this->engineFactory, $assertionFactory, $this->printer, $command, $cacheRepository, $testResultFactory);
        $testRunner             = $testRunnerFactory->make($testSuite);

        try {
            $filterFactory      = new GenericFilterFactory($this->argumentsParser);
            $filter             = $filterFactory->make();
        } catch (MalformedRegularExpressionPatternException $exception) {
            $this->printer->writeLn('The provided regular expression pattern is malformed.');
            $this->printer->writeLn('The following warning was issued:');
            $this->printer->writeLn("\"{$exception->getMessage()}\"");
            return 1;
        }

        // 3. Let **preparedTestCases** be the result of **testCaseFactory**.make() for every element of **testSuite**.[[paths]].
        $preparedTestCases = $testCaseFactory->make(...$testSuite->paths());

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
