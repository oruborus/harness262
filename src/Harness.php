<?php

declare(strict_types=1);

namespace Oru\Harness;

use Oru\Harness\Assertion\GenericAssertionFactory;
use Oru\Harness\Cache\GenericCacheRepositoryFactory;
use Oru\Harness\Cli\CliArgumentsParser;
use Oru\Harness\Cli\Exception\InvalidOptionException;
use Oru\Harness\Cli\Exception\UnknownOptionException;
use Oru\Harness\Command\ClonedPhpCommand;
use Oru\Harness\Config\Exception\InvalidPathException;
use Oru\Harness\Config\Exception\MalformedRegularExpressionPatternException;
use Oru\Harness\Config\Exception\MissingPathException;
use Oru\Harness\Config\GenericTestConfigFactory;
use Oru\Harness\Config\TestSuiteConfigFactory;
use Oru\Harness\Config\OutputConfigFactory;
use Oru\Harness\Config\PrinterConfigFactory;
use Oru\Harness\Contracts\Facade;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Helpers\TemporaryFileHandler;
use Oru\Harness\Output\GenericOutputFactory;
use Oru\Harness\Printer\GenericPrinterFactory;
use Oru\Harness\Storage\FileStorage;
use Oru\Harness\TestRunner\GenericTestRunnerFactory;

use function array_shift;
use function count;
use function file_get_contents;
use function realpath;
use function str_ends_with;
use function time;

final readonly class Harness
{
    private const TEMPLATE_PATH     = __DIR__ . '/Template/ExecuteTest';
    private const TARGET_PATH       = self::TEMPLATE_PATH . '.php';
    private const TEST_STORAGE_PATH = '.';
    private const CLI_OPTIONS       = [
        'no-cache' => 'n',
        'silent'   => 's',
        'verbose'  => 'v',
        'debug'    => null,
        'include'  => ':',
        'exclude'  => ':',
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
        $this->temporaryFileHandler = new TemporaryFileHandler(static::TARGET_PATH, $contents);
    }

    /**
     * @param list<string> $arguments
     *
     * @throws InvalidOptionException
     * @throws UnknownOptionException
     */
    public function run(array $arguments): int
    {
        array_shift($arguments);

        $testStorage            = new FileStorage(static::TEST_STORAGE_PATH);
        $argumentsParser        = new CliArgumentsParser($arguments, static::CLI_OPTIONS);
        $testConfigFactory      = new GenericTestConfigFactory($testStorage);
        $printerFactory         = new GenericPrinterFactory();
        $outputFactory          = new GenericOutputFactory();
        $assertionFactory       = new GenericAssertionFactory($this->facade);
        $command                = new ClonedPhpCommand(realpath(static::TARGET_PATH));

        $outputConfigFactory    = new OutputConfigFactory($argumentsParser);
        $outputConfig           = $outputConfigFactory->make();
        $output                 = $outputFactory->make($outputConfig);

        $printerConfigFactory   = new PrinterConfigFactory($argumentsParser);
        $printerConfig          = $printerConfigFactory->make();
        $printer                = $printerFactory->make($printerConfig, $output);

        // 1. Let testSuiteStartTime be the current system time in seconds.
        $testSuiteStartTime = time();

        // 2. Perform **printer**.start().
        $printer->start();

        try {
            $testSuiteConfigFactory = new TestSuiteConfigFactory($argumentsParser);
            $testSuiteConfig        = $testSuiteConfigFactory->make();
        } catch (MalformedRegularExpressionPatternException $exception) {
            $printer->writeLn('The provided regular expression pattern is malformed.');
            $printer->writeLn('The following warning was issued:');
            $printer->writeLn("\"{$exception->getMessage()}\"");
            return 1;
        } catch (InvalidPathException $exception) {
            $printer->writeLn($exception->getMessage());
            return 1;
        } catch (MissingPathException $exception) {
            // TODO: Print command usage here.
            $printer->writeLn($exception->getMessage());
            return 1;
        }

        $cacheRepositoryFactory = new GenericCacheRepositoryFactory();
        $cacheRepository       = $cacheRepositoryFactory->make($testSuiteConfig);

        $testRunnerFactory     = new GenericTestRunnerFactory($this->facade, $assertionFactory, $printer, $command, $cacheRepository);
        $testRunner            = $testRunnerFactory->make($testSuiteConfig);


        // 3. Let **preparedTestConfigurations** be a new empty list.
        /**
         * @var TestConfig[] $preparedTestConfigurations
         */
        $preparedTestConfigurations = [];

        // 4. For each **providedPath** of **config**.[[Paths]], do
        foreach ($testSuiteConfig->paths() as $providedPath) {
            // FIXME: a. If file is not a valid ECMAScript file, then skip.
            if (str_ends_with($providedPath, '_FIXTURE.js')) {
                continue;
            }

            // b. Let **testConfigs** the frontmatter configurations of the file stored at **providedPath**.
            $testConfigs = $testConfigFactory->make($providedPath);

            // iii. Append **testConfig** to **preparedTestConfigurations**.
            $preparedTestConfigurations = [...$preparedTestConfigurations, ...$testConfigs];
        }

        // 5. Perform **printer**.setStepCount(count(**preparedTestConfigurations**)).
        $printer->setStepCount(count($preparedTestConfigurations));

        // 6. Let **resultList** be a new empty list.
        $resultList = [];

        // 7. For each **testConfig** of **preparedTestConfigurations**, do
        foreach ($preparedTestConfigurations as $testConfig) {
            // a. Perform runTest(**testConfig**).
            $testRunner->run($testConfig);
        }

        // 8. Append the returned list of **testRunner.finalize()** to **resultList**.
        $resultList = [...$resultList, ...$testRunner->finalize()];

        // 9. Let **testSuiteEndTime** be the current system time in seconds.
        $testSuiteEndTime = time();

        // 10. Perform **printer**.end(**resultList**, **testSuiteEndTime** - **testSuiteStartTime**).
        $printer->end($resultList, $testSuiteEndTime - $testSuiteStartTime);

        return 0;
    }
}
