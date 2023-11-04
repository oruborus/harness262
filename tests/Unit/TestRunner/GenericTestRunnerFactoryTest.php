<?php

declare(strict_types=1);

namespace Tests\Unit\TestRunner;

use Generator;
use Oru\Harness\Contracts\AssertionFactory;
use Oru\Harness\Contracts\CacheRepository;
use Oru\Harness\Contracts\Command;
use Oru\Harness\Contracts\Facade;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Contracts\TestSuiteConfig;
use Oru\Harness\TestRunner\AsyncTestRunner;
use Oru\Harness\TestRunner\CacheTestRunner;
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
    #[DataProvider('provideDebugTestRunnerMode')]
    #[DataProvider('provideNonDebugTestRunnerMode')]
    public function createsTheCorrectTestRunnerBasedOnConfig(TestRunnerMode $mode, string $expected): void
    {
        $testSuiteConfigStub = $this->createConfiguredStub(TestSuiteConfig::class, [
            'testRunnerMode' => $mode,
            'cache' => false,
            'stopOnCharacteristic' => StopOnCharacteristic::Nothing
        ]);
        $factory = new GenericTestRunnerFactory(
            $this->createStub(Facade::class),
            $this->createStub(AssertionFactory::class),
            $this->createStub(Printer::class),
            $this->createStub(Command::class),
            $this->createStub(CacheRepository::class)
        );

        $actual = $factory->make($testSuiteConfigStub);

        $this->assertInstanceOf($expected, $actual);
    }

    public static function provideDebugTestRunnerMode(): Generator
    {
        yield 'linear'   => [TestRunnerMode::Linear, LinearTestRunner::class];
    }

    public static function provideNonDebugTestRunnerMode(): Generator
    {
        yield 'parallel' => [TestRunnerMode::Parallel, ParallelTestRunner::class];
        yield 'async'    => [TestRunnerMode::Async, AsyncTestRunner::class];
    }

    #[Test]
    #[DataProvider('provideDebugTestRunnerMode')]
    public function doesNotCreateCacheTestRunnerWhenInDebugModeAndCachingIsEnabled(TestRunnerMode $mode, string $expected): void
    {
        $testSuiteConfigStub = $this->createConfiguredStub(TestSuiteConfig::class, [
            'testRunnerMode' => $mode,
            'cache' => true,
            'stopOnCharacteristic' => StopOnCharacteristic::Nothing
        ]);
        $factory = new GenericTestRunnerFactory(
            $this->createStub(Facade::class),
            $this->createStub(AssertionFactory::class),
            $this->createStub(Printer::class),
            $this->createStub(Command::class),
            $this->createStub(CacheRepository::class)
        );

        $actual = $factory->make($testSuiteConfigStub);

        $this->assertInstanceOf($expected, $actual);
    }


    #[Test]
    #[DataProvider('provideNonDebugTestRunnerMode')]
    public function createsCacheTestRunnerWhenCachingIsEnabledForNonDebugModes(TestRunnerMode $mode): void
    {
        $factory = new GenericTestRunnerFactory(
            $this->createStub(Facade::class),
            $this->createStub(AssertionFactory::class),
            $this->createStub(Printer::class),
            $this->createStub(Command::class),
            $this->createStub(CacheRepository::class),
        );
        $testSuiteConfigStub = $this->createConfiguredStub(TestSuiteConfig::class, [
            'testRunnerMode' => $mode,
            'cache' => true,
            'stopOnCharacteristic' => StopOnCharacteristic::Nothing
        ]);
        $expectedTestSuiteConfigStub = $this->createConfiguredStub(TestSuiteConfig::class, [
            'testRunnerMode' => $mode,
            'cache' => false,
            'stopOnCharacteristic' => StopOnCharacteristic::Nothing
        ]);
        $expected = new CacheTestRunner(
            $this->createStub(CacheRepository::class),
            $factory->make($expectedTestSuiteConfigStub)
        );

        $actual = $factory->make($testSuiteConfigStub);

        $this->assertEquals($expected, $actual);
    }
}
