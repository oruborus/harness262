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

namespace Oru\Harness;

use Oru\Harness\Assertion\GenericAssertionFactory;
use Oru\Harness\Cache\GenericCacheRepositoryFactory;
use Oru\Harness\Cli\CliArgumentsParser;
use Oru\Harness\Cli\Exception\InvalidOptionException;
use Oru\Harness\Cli\Exception\UnknownOptionException;
use Oru\Harness\Command\ClonedPhpCommand;
use Oru\Harness\Config\Exception\InvalidPathException;
use Oru\Harness\Config\Exception\MissingFrontmatterException;
use Oru\Harness\Config\Exception\MissingPathException;
use Oru\Harness\Config\GenericTestConfigFactory;
use Oru\Harness\Config\TestSuiteConfigFactory;
use Oru\Harness\Config\OutputConfigFactory;
use Oru\Harness\Config\PrinterConfigFactory;
use Oru\Harness\Contracts\Facade;
use Oru\Harness\Filter\Exception\MalformedRegularExpressionPatternException;
use Oru\Harness\Filter\GenericFilterFactory;
use Oru\Harness\Frontmatter\Exception\MissingRequiredKeyException;
use Oru\Harness\Frontmatter\Exception\ParseException;
use Oru\Harness\Frontmatter\Exception\UnrecognizedKeyException;
use Oru\Harness\Frontmatter\Exception\UnrecognizedNegativePhaseException;
use Oru\Harness\Helpers\LogicalCoreCounter;
use Oru\Harness\Helpers\TemporaryFileHandler;
use Oru\Harness\Output\GenericOutputFactory;
use Oru\Harness\Printer\GenericPrinterFactory;
use Oru\Harness\Storage\FileStorage;
use Oru\Harness\TestRunner\GenericTestRunnerFactory;

use function array_shift;
use function count;
use function file_get_contents;
use function realpath;
use function time;

final readonly class Harness
{
    private const TEMPLATE_PATH     = __DIR__ . '/Template/ExecuteTest';
    private const TEST_STORAGE_PATH = '.';
    private const CLI_OPTIONS       = [
        'no-cache'        => 'n',
        'silent'          => 's',
        'verbose'         => 'v',
        'debug'           => null,
        'include'         => ':',
        'exclude'         => ':',
        'stop-on-defect'  => null,
        'stop-on-error'   => null,
        'stop-on-failure' => null,
        'concurrency'     => 'c:',
        'only-strict'     => null,
        'no-strict'       => null,
        'module'          => null,
        'async'           => null,
        'raw'             => null,
    ];

    private TemporaryFileHandler $temporaryFileHandler;

    public function __construct(
        private Facade $facade
    ) {
        $contents = str_replace(
            '{{FACADE_PATH}}',
            $this->facade->path(),
            file_get_contents(realpath(static::TEMPLATE_PATH))
        );
        $this->temporaryFileHandler = new TemporaryFileHandler($contents);
    }

    /**
     * @param list<string> $arguments
     *
     * @throws InvalidOptionException
     * @throws UnknownOptionException
     * @throws MissingFrontmatterException
     * @throws MissingRequiredKeyException
     * @throws UnrecognizedKeyException
     * @throws UnrecognizedNegativePhaseException
     * @throws ParseException
     */
    public function run(array $arguments): int
    {
        array_shift($arguments);

        $testStorage            = new FileStorage(static::TEST_STORAGE_PATH);
        $argumentsParser        = new CliArgumentsParser($arguments, static::CLI_OPTIONS);
        $printerFactory         = new GenericPrinterFactory();
        $outputFactory          = new GenericOutputFactory();
        $assertionFactory       = new GenericAssertionFactory($this->facade);
        $command                = new ClonedPhpCommand(realpath($this->temporaryFileHandler->path()));

        $outputConfigFactory    = new OutputConfigFactory($argumentsParser);
        $outputConfig           = $outputConfigFactory->make();
        $output                 = $outputFactory->make($outputConfig);

        $printerConfigFactory   = new PrinterConfigFactory($argumentsParser);
        $printerConfig          = $printerConfigFactory->make();
        $printer                = $printerFactory->make($printerConfig, $output);

        $coreCounter            = new LogicalCoreCounter();

        // 1. Let testSuiteStartTime be the current system time in seconds.
        $testSuiteStartTime = time();

        // 2. Perform **printer**.start().
        $printer->start();

        try {
            $testSuiteConfigFactory = new TestSuiteConfigFactory($argumentsParser, $coreCounter);
            $testSuiteConfig        = $testSuiteConfigFactory->make();
        } catch (InvalidPathException $exception) {
            $printer->writeLn($exception->getMessage());
            return 1;
        } catch (MissingPathException $exception) {
            // TODO: Print command usage here.
            $printer->writeLn($exception->getMessage());
            return 1;
        }

        $testConfigFactory      = new GenericTestConfigFactory($testStorage, $testSuiteConfig);
        $cacheRepositoryFactory = new GenericCacheRepositoryFactory();
        $cacheRepository        = $cacheRepositoryFactory->make($testSuiteConfig);

        $testRunnerFactory      = new GenericTestRunnerFactory($this->facade, $assertionFactory, $printer, $command, $cacheRepository);
        $testRunner             = $testRunnerFactory->make($testSuiteConfig);

        try {
            $filterFactory      = new GenericFilterFactory($argumentsParser);
            $filter             = $filterFactory->make();
        } catch (MalformedRegularExpressionPatternException $exception) {
            $printer->writeLn('The provided regular expression pattern is malformed.');
            $printer->writeLn('The following warning was issued:');
            $printer->writeLn("\"{$exception->getMessage()}\"");
            return 1;
        }

        // 3. Let **preparedTestConfigurations** be the result of **testConfigFactory**.make() for every element of **testSuiteConfig**.[[paths]].
        $preparedTestConfigurations = $testConfigFactory->make(...$testSuiteConfig->paths());

        // 4. Let **filteredTestConfigurations** be the result of **filter**.apply() for every element of **preparedTestConfigurations**.
        $filteredTestConfigurations = $filter->apply(...$preparedTestConfigurations);

        // 5. Perform **printer**.setStepCount(count(**preparedTestConfigurations**)).
        $printer->setStepCount(count($filteredTestConfigurations));

        // 6. For each **testConfig** of **filteredTestConfigurations**, do
        foreach ($filteredTestConfigurations as $testConfig) {
            // a. Perform **testRunner**.add(**testConfig**).
            $testRunner->add($testConfig);
        }

        // 7. Let **testSuiteEndTime** be the current system time in seconds.
        $testSuiteEndTime = time();

        // 8. Perform **printer**.end(**testRunner**.run(), **testSuiteEndTime** - **testSuiteStartTime**).
        $printer->end($testRunner->run(), $testSuiteEndTime - $testSuiteStartTime);

        return 0;
    }
}
