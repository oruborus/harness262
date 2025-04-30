<?php

/**
 * Copyright (c) 2023-2025, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Tests\Unit\TestRunner;

use Generator;
use Oru\Harness\Contracts\AssertionFactory;
use Oru\Harness\Contracts\CacheRepository;
use Oru\Harness\Contracts\CacheRepositoryFactory;
use Oru\Harness\Contracts\Command;
use Oru\Harness\Contracts\EngineFactory;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\TestResultFactory;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Contracts\TestSuite;
use Oru\Harness\TestRunner\PhpSubprocessTestRunner;
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
        $testSuiteStub = $this->createConfiguredStub(TestSuite::class, [
            'testRunnerMode' => $mode,
            'cache' => false,
            'stopOnCharacteristic' => StopOnCharacteristic::Nothing
        ]);
        $factory = new GenericTestRunnerFactory(
            $this->createStub(EngineFactory::class),
            $this->createStub(AssertionFactory::class),
            $this->createStub(Printer::class),
            $this->createStub(Command::class),
            $this->createStub(CacheRepositoryFactory::class),
            $this->createStub(TestResultFactory::class),
        );

        $actual = $factory->make($testSuiteStub);

        $this->assertInstanceOf($expected, $actual);
    }

    public static function provideDebugTestRunnerMode(): Generator
    {
        yield 'linear'   => [TestRunnerMode::Linear, LinearTestRunner::class];
    }

    public static function provideNonDebugTestRunnerMode(): Generator
    {
        yield 'parallel' => [TestRunnerMode::Parallel, ParallelTestRunner::class];
        yield 'async'    => [TestRunnerMode::Async, PhpSubprocessTestRunner::class];
    }

    #[Test]
    #[DataProvider('provideDebugTestRunnerMode')]
    public function doesNotCreateCacheTestRunnerWhenInDebugModeAndCachingIsEnabled(TestRunnerMode $mode, string $expected): void
    {
        $testSuiteStub = $this->createConfiguredStub(TestSuite::class, [
            'testRunnerMode' => $mode,
            'cache' => true,
            'stopOnCharacteristic' => StopOnCharacteristic::Nothing
        ]);
        $factory = new GenericTestRunnerFactory(
            $this->createStub(EngineFactory::class),
            $this->createStub(AssertionFactory::class),
            $this->createStub(Printer::class),
            $this->createStub(Command::class),
            $this->createStub(CacheRepositoryFactory::class),
            $this->createStub(TestResultFactory::class),
        );

        $actual = $factory->make($testSuiteStub);

        $this->assertInstanceOf($expected, $actual);
    }


    #[Test]
    #[DataProvider('provideNonDebugTestRunnerMode')]
    public function createsCacheTestRunnerWhenCachingIsEnabledForNonDebugModes(TestRunnerMode $mode): void
    {
        $cacheRepositoryStub = $this->createStub(CacheRepository::class);

        $factory = new GenericTestRunnerFactory(
            $this->createStub(EngineFactory::class),
            $this->createStub(AssertionFactory::class),
            $this->createStub(Printer::class),
            $this->createStub(Command::class),
            $this->createConfiguredStub(CacheRepositoryFactory::class, ['make' => $cacheRepositoryStub]),
            $this->createStub(TestResultFactory::class),
        );
        $testSuiteStub = $this->createConfiguredStub(TestSuite::class, [
            'testRunnerMode' => $mode,
            'cache' => true,
            'stopOnCharacteristic' => StopOnCharacteristic::Nothing
        ]);
        $expectedTestSuiteStub = $this->createConfiguredStub(TestSuite::class, [
            'testRunnerMode' => $mode,
            'cache' => false,
            'stopOnCharacteristic' => StopOnCharacteristic::Nothing
        ]);
        $expected = new CacheTestRunner(
            $cacheRepositoryStub,
            $factory->make($expectedTestSuiteStub),
            $this->createStub(TestResultFactory::class),
        );

        $actual = $factory->make($testSuiteStub);

        $this->assertEquals($expected, $actual);
    }
}
