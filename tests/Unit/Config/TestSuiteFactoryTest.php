<?php

/**
 * Copyright (c) 2023, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Tests\Unit\Config;

use Generator;
use Oru\Harness\Config\Exception\InvalidPathException;
use Oru\Harness\Config\Exception\MissingPathException;
use Oru\Harness\Config\TestSuiteFactory;
use Oru\Harness\Contracts\CoreCounter;
use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Contracts\TestSuite;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Utility\ArgumentsParser\ArgumentsParserStub;

#[CoversClass(TestSuiteFactory::class)]
final class TestSuiteFactoryTest extends TestCase
{
    #[Test]
    public function createsConfigForTestSuite(): void
    {
        $argumentsParserStub = new ArgumentsParserStub([], [__DIR__ . '/../Fixtures/Basic']);
        $factory = new TestSuiteFactory($argumentsParserStub, $this->createStub(CoreCounter::class));

        $actual = $factory->make();

        $this->assertInstanceOf(TestSuite::class, $actual);
    }

    #[Test]
    public function interpretsAllNonPrefixedArgumentsAsPaths(): void
    {
        $expected = [__DIR__ . '/../Fixtures/Basic/PATH0', __DIR__ . '/../Fixtures/Basic/PATH1', __DIR__ . '/../Fixtures/Basic/PATH2'];
        $argumentsParserStub = new ArgumentsParserStub([], $expected);
        $factory = new TestSuiteFactory($argumentsParserStub, $this->createStub(CoreCounter::class));

        $actual = $factory->make();

        $this->assertSame($expected, $actual->paths());
    }

    #[Test]
    public function runsAllProvidedTestPathsAndIgnoresFixtures(): void
    {
        $expected = [
            './tests/Unit/Fixtures/TestCase/async.js',
            './tests/Unit/Fixtures/TestCase/basic.js',
            './tests/Unit/Fixtures/TestCase/module.js',
            './tests/Unit/Fixtures/TestCase/noStrict.js',
            './tests/Unit/Fixtures/TestCase/onlyStrict.js',
            './tests/Unit/Fixtures/TestCase/raw.js',
            './tests/Unit/Fixtures/TestCase/raw.js',
        ];
        $paths = [
            './tests/Unit/Fixtures/TestCase',
            './tests/Unit/Fixtures/TestCase/raw.js',
            './tests/Unit/Fixtures/TestCase/test_FIXTURE.js',
        ];
        $argumentsParserStub = new ArgumentsParserStub([], $paths);
        $factory = new TestSuiteFactory($argumentsParserStub, $this->createStub(CoreCounter::class));

        $actual = $factory->make();

        $this->assertEqualsCanonicalizing($expected, $actual->paths());
    }

    #[Test]
    public function failsWhenPathsIsEmpty(): void
    {
        $this->expectExceptionObject(new MissingPathException('No test path specified. Aborting.'));

        $factory = new TestSuiteFactory(new ArgumentsParserStub(), $this->createStub(CoreCounter::class));

        $factory->make();
    }

    #[Test]
    public function defaultConfigForCachingIsTrue(): void
    {
        $argumentsParserStub = new ArgumentsParserStub([], [__DIR__ . '/../Fixtures/Basic']);
        $factory = new TestSuiteFactory($argumentsParserStub, $this->createStub(CoreCounter::class));

        $actual = $factory->make();

        $this->assertTrue($actual->cache());
    }

    #[Test]
    public function defaultConcurrencyIsEqualToTheLogicalCoreCountOfTheMachine(): void
    {
        $expected = 123456;
        $argumentsParserStub = new ArgumentsParserStub([], [__DIR__ . '/../Fixtures/Basic']);
        $coreCounterStub = $this->createConfiguredStub(CoreCounter::class, ['count' => $expected]);
        $factory = new TestSuiteFactory($argumentsParserStub, $coreCounterStub);

        $actual = $factory->make();

        $this->assertSame($expected, $actual->concurrency());
    }

    #[Test]
    #[DataProvider('provideConcurrency')]
    public function concurrencyCanBeConfiguredAndIsClampedBetween1AndLogicalCpuCount(int $input, int $expected): void
    {
        $argumentsParserStub = new ArgumentsParserStub(['concurrency' => "{$input}"], [__DIR__ . '/../Fixtures/Basic']);
        $coreCounterStub = $this->createConfiguredStub(CoreCounter::class, ['count' => 500]);
        $factory = new TestSuiteFactory($argumentsParserStub, $coreCounterStub);

        $actual = $factory->make();

        $this->assertSame($expected, $actual->concurrency());
    }

    public static function provideConcurrency(): Generator
    {
        yield 'within bounds' => [10, 10];
        yield 'below bounds'  => [-10, 1];
        yield 'above bounds'  => [1000, 500];
    }

    #[Test]
    public function cachingCanBeDisabled(): void
    {
        $argumentsParserStub = new ArgumentsParserStub(['no-cache' => null], [__DIR__ . '/../Fixtures/Basic']);
        $factory = new TestSuiteFactory($argumentsParserStub, $this->createStub(CoreCounter::class));

        $actual = $factory->make();

        $this->assertFalse($actual->cache());
    }

    #[Test]
    public function defaultConfigForRunnerModeIsAsync(): void
    {
        $argumentsParserStub = new ArgumentsParserStub([], [__DIR__ . '/../Fixtures/Basic']);
        $factory = new TestSuiteFactory($argumentsParserStub, $this->createStub(CoreCounter::class));

        $actual = $factory->make([]);

        $this->assertSame(TestRunnerMode::Async, $actual->testRunnerMode());
    }

    #[Test]
    public function configForRunnerModeCanBeSetToLinear(): void
    {
        $argumentsParserStub = new ArgumentsParserStub(['debug' => null], [__DIR__ . '/../Fixtures/Basic']);
        $factory = new TestSuiteFactory($argumentsParserStub, $this->createStub(CoreCounter::class));

        $actual = $factory->make();

        $this->assertSame(TestRunnerMode::Linear, $actual->testRunnerMode());
    }

    #[Test]
    public function failsWhenProvidedPathDoesNotExist(): void
    {
        $this->expectExceptionObject(new InvalidPathException("Provided path `AAA` does not exist"));

        $factory = new TestSuiteFactory(new ArgumentsParserStub([], ['AAA']), $this->createStub(CoreCounter::class));

        $factory->make();
    }

    #[Test]
    public function addsValidDirectoryContentsRecursivelyToPaths(): void
    {
        $argumentsParserStub = new ArgumentsParserStub([], [__DIR__ . '/../Fixtures/Basic']);
        $factory = new TestSuiteFactory($argumentsParserStub, $this->createStub(CoreCounter::class));

        $actual = $factory->make();

        $this->assertCount(6, $actual->paths());
    }

    #[Test]
    public function defaultStopOnCharacteristicIsNothing(): void
    {
        $argumentsParserStub = new ArgumentsParserStub([], [__DIR__ . '/../Fixtures/Basic']);
        $factory = new TestSuiteFactory($argumentsParserStub, $this->createStub(CoreCounter::class));

        $actual = $factory->make();

        $this->assertSame(StopOnCharacteristic::Nothing, $actual->stopOnCharacteristic());
    }

    #[Test]
    #[DataProvider('provideStopOnOptions')]
    public function stopOnCharacteristicCanBeChanged(array $options, StopOnCharacteristic $expected): void
    {
        $argumentsParserStub = new ArgumentsParserStub($options, [__DIR__ . '/../Fixtures/Basic']);
        $factory = new TestSuiteFactory($argumentsParserStub, $this->createStub(CoreCounter::class));

        $actual = $factory->make();

        $this->assertSame($expected, $actual->stopOnCharacteristic());
    }

    public static function provideStopOnOptions(): Generator
    {
        yield 'stop on defect'             => [['stop-on-defect' => null], StopOnCharacteristic::Defect];
        yield 'stop on error'              => [['stop-on-error' => null], StopOnCharacteristic::Error];
        yield 'stop on failure'            => [['stop-on-failure' => null], StopOnCharacteristic::Failure];
        yield 'stop on error and failure'  => [['stop-on-error' => null, 'stop-on-failure' => null], StopOnCharacteristic::Defect];
    }
}
