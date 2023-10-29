<?php

declare(strict_types=1);

namespace Oru\Harness;

use Oru\Harness\Assertion\GenericAssertionFactory;
use Oru\Harness\Cache\GenericCacheRepository;
use Oru\Harness\Cache\NoCacheRepository;
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
use Oru\Harness\Contracts\CacheResultRecord;
use Oru\Harness\Contracts\Facade;
use Oru\Harness\Contracts\Storage;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Loop\TaskLoop;
use Oru\Harness\Output\GenericOutputFactory;
use Oru\Harness\Printer\GenericPrinterFactory;
use Oru\Harness\Storage\FileStorage;
use Oru\Harness\Storage\SerializingFileStorage;
use Oru\Harness\TestRunner\AsyncTestRunner;
use Oru\Harness\TestRunner\GenericTestResult;
use Oru\Harness\TestRunner\LinearTestRunner;
use Oru\Harness\TestRunner\ParallelTestRunner;

use function array_shift;
use function count;
use function file_get_contents;
use function file_put_contents;
use function is_null;
use function realpath;
use function str_ends_with;
use function time;
use function unlink;

final readonly class Harness
{
    private const TEMPLATE_PATH = __DIR__ . '/Template/ExecuteTest';

    public function __construct(
        private Facade $facade
    ) {
        file_put_contents(
            static::TEMPLATE_PATH . '.php',
            str_replace(
                '{{FACADE_PATH}}',
                $this->facade::path(),
                file_get_contents(realpath(static::TEMPLATE_PATH))
            )
        );
    }

    public function __destruct()
    {
        unlink(static::TEMPLATE_PATH . '.php');
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

        $testStorage       = new FileStorage('.');
        $argumentsParser   = new CliArgumentsParser(
            $arguments,
            [
                'no-cache' => 'n',
                'silent'   => 's',
                'verbose'  => 'v',
                'debug'    => null,
                'include'  => ':',
                'exclude'  => ':',
            ]
        );
        $testConfigFactory      = new GenericTestConfigFactory($testStorage);
        $printerFactory         = new GenericPrinterFactory();
        $outputFactory          = new GenericOutputFactory();
        $assertionFactory       = new GenericAssertionFactory($this->facade);
        $command                = new ClonedPhpCommand(realpath(static::TEMPLATE_PATH . '.php'));

        $outputConfigFactory    = new OutputConfigFactory($argumentsParser);
        $outputConfig           = $outputConfigFactory->make();
        $output                 = $outputFactory->make($outputConfig);

        $printerConfigFactory   = new PrinterConfigFactory($argumentsParser);
        $printerConfig          = $printerConfigFactory->make();
        $printer                = $printerFactory->make($printerConfig, $output, 0);

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

        // FIXME: Move to `CacheRepositoryFactory`
        /** 
         * @var Storage<CacheResultRecord> $storage
         */
        $storage = new SerializingFileStorage('./.harness/cache');
        $cacheRepository = $testSuiteConfig->cache() ?
            new GenericCacheRepository(
                $storage,
                static fn (TestConfig $i): string => md5(serialize($i)),
                static fn (string $i): string => hash_file('haval160,4', $i)
            ) :
            new NoCacheRepository();

        // FIXME: Move to `TestRunnerFactory`
        $testRunner = match ($testSuiteConfig->testRunnerMode()) {
            TestRunnerMode::Linear => new LinearTestRunner($this->facade, $assertionFactory, $printer),
            TestRunnerMode::Parallel => new ParallelTestRunner($assertionFactory, $printer, $command),
            TestRunnerMode::Async => new AsyncTestRunner(new ParallelTestRunner($assertionFactory, $printer, $command), new TaskLoop(8))
        };


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
            // a. Let **testStartTime** be the current system time in seconds.
            $testStartTime = time();

            // b. Let **cacheResult** be **cacheRepository**.get(**testConfig**).
            $cacheResult = $cacheRepository->get($testConfig);

            // c. If **cacheResult** is not `null`, then
            if (!is_null($cacheResult)) {
                // i. Let **testEndTime** be the current system time in seconds.
                $testEndTime = time();

                // ii. Let **cacheResult** be a new TestResult instance.
                $cacheResult = new GenericTestResult(
                    // iii. Set **cacheResult**.state to `cache`.
                    state: TestResultState::Cache,

                    // iv: Set **cacheResult**.path to **testConfig**.path()
                    path: $testConfig->path(),

                    // v. Set **cacheResult**.duration to **testEndTime** - **testStartTime**.
                    duration: $testEndTime - $testStartTime,

                    // vi. Set **cacheResult**.usedFiles to **cache**.usedFiles.
                    usedFiles: $cacheResult->usedFiles()
                );

                // vii. Append **cacheResult** to **resultList**.
                $resultList[] = $cacheResult;

                // viii. Perform **printer**.step(cache).
                $printer->step(TestResultState::Cache);

                // ix. Continue.
                continue;
            }

            // d. Perform runTest(**testConfig**).
            $testRunner->run($testConfig);

            // e. Let **testEndTime** be the current system time in seconds.
            $testEndTime = time();
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
