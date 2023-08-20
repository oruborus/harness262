<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness;

use Oru\EcmaScript\Harness\Assertion\GenericAssertionFactory;
use Oru\EcmaScript\Harness\Cache\GenericCacheRepository;
use Oru\EcmaScript\Harness\Cache\NoCacheRepository;
use Oru\EcmaScript\Harness\Contracts\TestResultState;
use Oru\EcmaScript\Harness\Contracts\TestRunnerMode;
use Oru\EcmaScript\Harness\Output\GenericOutputFactory;
use Oru\EcmaScript\Harness\Printer\GenericPrinterFactory;
use Oru\EcmaScript\Harness\Storage\FileStorage;
use Oru\EcmaScript\Harness\Storage\SerializingFileStorage;
use Oru\EcmaScript\Harness\Test\GenericTestConfigFactory;
use Oru\EcmaScript\Harness\Test\GenericTestResult;
use Oru\EcmaScript\Harness\Test\LinearTestRunner;
use Oru\EcmaScript\Harness\Test\ParallelTestRunner;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

use function array_shift;
use function count;
use function file_exists;
use function is_dir;
use function is_file;
use function is_null;
use function time;

final readonly class Harness
{
    /**
     * @param string[] $arguments
     */
    public function run(array $arguments): int
    {
        array_shift($arguments);

        $engine = getEngine();

        $testStorage       = new FileStorage('.');
        $configFactory     = new HarnessConfigFactory();
        $testConfigFactory = new GenericTestConfigFactory($testStorage);
        $printerFactory    = new GenericPrinterFactory();
        $outputFactory     = new GenericOutputFactory();
        $assertionFactory  = new GenericAssertionFactory();

        $config  = $configFactory->make($arguments);
        $output  = $outputFactory->make($config);
        $printer = $printerFactory->make($config, $output, 0);

        // FIXME: Move to `CacheRepositoryFactory`
        $cacheRepository = $config->cache() ?
            new GenericCacheRepository(new SerializingFileStorage('./.harness/cache')) :
            new NoCacheRepository();

        // FIXME: Move to `TestRunnerFactory`
        $testRunner = match ($config->testRunnerMode()) {
            TestRunnerMode::Linear => new LinearTestRunner($engine, $printer, $assertionFactory),
            TestRunnerMode::Parallel => new ParallelTestRunner($engine, $printer, $assertionFactory)
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

            // d. Let **testResult** be runTest(**testConfig**).
            $testResult = $testRunner->run($testConfig);

            // e. Let **testEndTime** be the current system time in seconds.
            $testEndTime = time();

            // f. Set **testResult**.duration to `testEndTime - testStartTime`.
            $testResult->duration($testEndTime - $testStartTime);

            // g. If **testResult**.state is `success`, then
            if ($testResult->state() === TestResultState::Success) {
                // ii. Perform **cacheRepository**.set(**testConfig**, **testResult**).
                $cacheRepository->set($testConfig, $testResult);
            }

            // h. Append **testResult** to **resultList**.
            $resultList[] = $testResult;
        }

        // 8. Let **testSuiteEndTime** be the current system time in seconds.
        $testSuiteEndTime = time();

        // 9. Perform **printer**.end(**resultList**, **testSuiteEndTime** - **testSuiteStartTime**).
        $printer->end($resultList, $testSuiteEndTime - $testSuiteStartTime);

        return 0;
    }
}
