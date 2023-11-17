<?php

declare(strict_types=1);

namespace Oru\Harness\TestRunner;

use Oru\Harness\Contracts\CacheRepository;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Contracts\TestRunner;

final class CacheTestRunner implements TestRunner
{
    /**
     * @var TestResult[] $results
     */
    private array $results = [];

    public function __construct(
        private CacheRepository $cacheRepository,
        private TestRunner $testRunner
    ) {}

    public function add(TestConfig $config): void
    {
        if ($testResult = $this->cacheRepository->get($config)) {
            $this->results[] = new GenericTestResult(TestResultState::Cache, $config->path(), $testResult->usedFiles(), 0);
            return;
        }

        $this->testRunner->add($config);
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
