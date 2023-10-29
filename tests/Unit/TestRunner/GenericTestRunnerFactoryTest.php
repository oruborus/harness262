<?php

declare(strict_types=1);

namespace Tests\Unit\TestRunner;

use Generator;
use Oru\Harness\Contracts\AssertionFactory;
use Oru\Harness\Contracts\Command;
use Oru\Harness\Contracts\Facade;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Contracts\TestSuiteConfig;
use Oru\Harness\TestRunner\AsyncTestRunner;
use Oru\Harness\TestRunner\GenericTestRunnerFactory;
use Oru\Harness\TestRunner\LinearTestRunner;
use Oru\Harness\TestRunner\ParallelTestRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericTestRunnerFactory::class)]
final class GenericTestRunnerFactoryTest extends TestCase
{
    #[Test]
    #[DataProvider('provideTestRunnerMode')]
    public function createsTheCorrectTestRunnerBasedOnConfig(TestRunnerMode $mode, string $expected): void
    {
        $testSuiteConfigStub = $this->createConfiguredStub(TestSuiteConfig::class, [
            'testRunnerMode' => $mode
        ]);
        $factory = new GenericTestRunnerFactory(
            $this->createStub(Facade::class),
            $this->createStub(AssertionFactory::class),
            $this->createStub(Printer::class),
            $this->createStub(Command::class),
        );

        $actual = $factory->make($testSuiteConfigStub);

        $this->assertInstanceOf($expected, $actual);
    }

    public static function provideTestRunnerMode(): Generator
    {
        yield 'linear'   => [TestRunnerMode::Linear, LinearTestRunner::class];
        yield 'parallel' => [TestRunnerMode::Parallel, ParallelTestRunner::class];
        yield 'async'    => [TestRunnerMode::Async, AsyncTestRunner::class];
    }
}
