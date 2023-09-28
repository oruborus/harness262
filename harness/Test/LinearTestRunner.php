<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Test;

use Oru\EcmaScript\Harness\Contracts\AssertionFactory;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestResult;
use Oru\EcmaScript\Harness\Contracts\TestResultState;
use Oru\EcmaScript\Harness\Contracts\TestRunner;
use Oru\EcmaScript\Harness\Assertion\Exception\AssertionFailedException;
use Oru\EcmaScript\Harness\Contracts\Facade;
use Oru\EcmaScript\Harness\Contracts\Printer;
use Throwable;

use function array_diff;
use function count;

final class LinearTestRunner implements TestRunner
{
    /**
     * @var TestResult[] $results
     */
    private array $results = [];

    public function __construct(
        private readonly Facade $facade,
        private readonly AssertionFactory $assertionFactory,
        private readonly Printer $printer
    ) {
    }

    public function run(TestConfig $config): void
    {
        $differences = array_diff($config->frontmatter()->features(), $this->facade->engineSupportedFeatures());

        if (count($differences) > 0) {
            $this->addResult(new GenericTestResult(TestResultState::Skip, [], 0));
            return;
        }

        foreach ($config->frontmatter()->includes() as $include) {
            $this->facade->engineAddFiles($include->value);
        }

        $this->facade->engineAddCode($config->content());

        try {
            /**
             * @psalm-suppress MixedAssignment  The methods of `Facade` intentionally return `mixed`
             */
            $actual = $this->facade->engineRun();
        } catch (Throwable $throwable) {
            $this->addResult(new GenericTestResult(TestResultState::Error, [], 0, $throwable));
            return;
        }

        $assertion = $this->assertionFactory->make($config);

        try {
            $assertion->assert($actual);
        } catch (AssertionFailedException $assertionFailedException) {
            $this->addResult(new GenericTestResult(TestResultState::Fail, [], 0, $assertionFailedException));
            return;
        }

        $this->addResult(new GenericTestResult(TestResultState::Success, [], 0));
        return;
    }

    private function addResult(TestResult $result): void
    {
        $this->results[] = $result;
        $this->printer->step($result->state());
    }

    /**
     * @return TestResult[]
     */
    public function finalize(): array
    {
        return $this->results;
    }
}
