<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Test;

use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\EcmaScript\Harness\Contracts\AssertionFactory;
use Oru\EcmaScript\Harness\Contracts\Printer;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestResult;
use Oru\EcmaScript\Harness\Contracts\TestResultState;
use Oru\EcmaScript\Harness\Contracts\TestRunner;
use Oru\EcmaScript\Harness\Assertion\Exception\AssertionFailedException;
use RuntimeException;
use Throwable;

use function array_diff;
use function count;

final readonly class LinearTestRunner implements TestRunner
{
    public function __construct(
        private Engine $engine,
        private AssertionFactory $assertionFactory
    ) {
    }

    public function run(TestConfig $config): TestResult
    {
        $differences = array_diff($config->frontmatter()->features(), $this->engine->getSupportedFeatures());

        if (count($differences) > 0) {
            return new GenericTestResult(TestResultState::Skip, [], 0);
        }

        foreach ($config->frontmatter()->includes() as $include) {
            $this->engine->addFiles($include->value);
        }

        $this->engine->addCode($config->content());

        try {
            $actual = $this->engine->run();
        } catch (Throwable $throwable) {
            return new GenericTestResult(TestResultState::Error, [], 0, $throwable);
        }

        $assertion = $this->assertionFactory->make($this->engine->getAgent(), $config);

        try {
            $assertion->assert($actual);
        } catch (AssertionFailedException $assertionFailedException) {
            return new GenericTestResult(TestResultState::Fail, [], 0, $assertionFailedException);
        }

        return new GenericTestResult(TestResultState::Success, [], 0);
    }

    public static function executeTest(Engine $engine, TestConfig $config, AssertionFactory $assertionFactory): TestResult
    {
        throw new RuntimeException('UNREACHABLE');
    }
}
