<?php

declare(strict_types=1);

namespace Oru\Harness;

use Iterator;
use Oru\Harness\Assertion\GenericAssertionFactory;
use Oru\Harness\Cache\GenericCacheRepository;
use Oru\Harness\Cache\NoCacheRepository;
use Oru\Harness\Command\ClonedPhpCommand;
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
use Oru\Harness\Test\AsyncTestRunner;
use Oru\Harness\Test\GenericTestConfigFactory;
use Oru\Harness\Test\GenericTestResult;
use Oru\Harness\Test\LinearTestRunner;
use Oru\Harness\Test\ParallelTestRunner;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Stringable;

use function array_shift;
use function count;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function is_file;
use function is_null;
use function realpath;
use function time;
use function unlink;

final readonly class Harness
{
    private const TEMPLATE_PATH = './src/Template/ExecuteTest';

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
     * @param string[] $arguments
     *
     * @throws RuntimeException
     */
    public function run(array $arguments): int
    {
        array_shift($arguments);

        $testStorage       = new FileStorage('.');
        $configFactory     = new HarnessConfigFactory();
        $testConfigFactory = new GenericTestConfigFactory($testStorage);
        $printerFactory    = new GenericPrinterFactory();
        $outputFactory     = new GenericOutputFactory();
        $assertionFactory  = new GenericAssertionFactory($this->facade);
        $command           = new ClonedPhpCommand(realpath(static::TEMPLATE_PATH . '.php'));

        $config  = $configFactory->make($arguments);
        $output  = $outputFactory->make($config);
        $printer = $printerFactory->make($config, $output, 0);

        // FIXME: Move to `CacheRepositoryFactory`
        /** 
         * @var Storage<CacheResultRecord> $storage
         */
        $storage = new SerializingFileStorage('./.harness/cache');
        $cacheRepository = $config->cache() ?
            new GenericCacheRepository($storage) :
            new NoCacheRepository();

        // FIXME: Move to `TestRunnerFactory`
        $testRunner = match ($config->testRunnerMode()) {
            TestRunnerMode::Linear => new LinearTestRunner($this->facade, $assertionFactory, $printer),
            TestRunnerMode::Parallel => new ParallelTestRunner($assertionFactory, $printer, $command),
            TestRunnerMode::Async => new AsyncTestRunner(new ParallelTestRunner($assertionFactory, $printer, $command), new TaskLoop(8))
        };


        // 1. Let testSuiteStartTime be the current system time in seconds.
        $testSuiteStartTime = time();

        // 2. Perform **printer**.start().
        $printer->start();

        // 3. Let **preparedTestConfigurations** be a new empty list.
        /**
         * @var TestConfig[] $preparedTestConfigurations
         */
        $preparedTestConfigurations = [];

        // 4. For each **providedPath** of **config**.[[Paths]], do
        foreach ($config->paths() as $providedPath) {
            // a. If **providedPath** does not exist, then throw a RuntimeException.
            if (!file_exists($providedPath)) {
                throw new RuntimeException("Provided path `{$providedPath}` does not exist");
            }

            // b. If **providedPath** points to a file, then
            if (is_file($providedPath)) {
                // FIXME: i. If file is not a valid ECMAScript file, then throw a RuntimeException.

                // ii. Let **testConfigs** the frontmatter configurations of the file stored at **providedPath**.
                $testConfigs = $testConfigFactory->make($providedPath);

                // iii. Append **testConfig** to **preparedTestConfigurations**.
                $preparedTestConfigurations = [...$preparedTestConfigurations, ...$testConfigs];
            }

            // c. Else, if **providedPath** points to a directory, then
            elseif (is_dir($providedPath)) {
                // i. For each recursively contained file **filePath** in **providedPath**, do
                /**
                 * @var Iterator<Stringable> $it
                 */
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($providedPath, RecursiveDirectoryIterator::SKIP_DOTS)
                );
                foreach ($it as $filePath) {
                    // FIXME: 1. If file is not a valid ECMAScript file, then continue.

                    // 2. Let **testConfigs** the frontmatter configurations of the file stored at **filePath**.
                    $testConfigs = $testConfigFactory->make((string) $filePath);

                    // 3. Append **testConfigs** to **preparedTestConfigurations**.
                    $preparedTestConfigurations = [...$preparedTestConfigurations, ...$testConfigs];
                }
            }

            // d. Else
            else {
                // i. Throw a RuntimeException.
                throw new RuntimeException("Provided path `{$providedPath}` does neither point to a directory nor a file");
            }
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

                    // iv. Set **cacheResult**.duration to **testEndTime** - **testStartTime**.
                    duration: $testEndTime - $testStartTime,

                    // v. Set **cacheResult**.usedFiles to **cache**.usedFiles.
                    usedFiles: $cacheResult->usedFiles()
                );

                // vi. Append **cacheResult** to **resultList**.
                $resultList[] = $cacheResult;

                // vii. Perform **printer**.step(cache).
                $printer->step(TestResultState::Cache);

                // viii. Continue.
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
