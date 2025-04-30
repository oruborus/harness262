<?php

/**
 * Copyright (c) 2023-2024, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Tests\Unit\TestSuite;

use Generator;
use Oru\Harness\Contracts\ArgumentsParser;
use Oru\Harness\Contracts\CoreCounter;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Contracts\TestSuite;
use Oru\Harness\TestSuite\Exception\InvalidPathException;
use Oru\Harness\TestSuite\Exception\MissingPathException;
use Oru\Harness\TestSuite\GenericTestSuiteFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Utility\ArgumentsParser\ArgumentsParserStub;

#[CoversClass(GenericTestSuiteFactory::class)]
final class GenericTestSuiteFactoryTest extends TestCase
{
    private function createTestSuiteFactory(
        ?ArgumentsParser $argumentsParser = null,
        ?CoreCounter $coreCounter = null,
        ?Printer $printer = null,
    ): GenericTestSuiteFactory {
        return new GenericTestSuiteFactory(
            $argumentsParser ?? $this->createStub(ArgumentsParser::class),
            $coreCounter ?? $this->createStub(CoreCounter::class),
            $printer ?? $this->createStub(Printer::class),
        );
    }

    #[Test]
    public function createsConfigForTestSuite(): void
    {
        $argumentsParserStub = new ArgumentsParserStub([], [__DIR__ . '/../Fixtures/Basic']);
        $factory = $this->createTestSuiteFactory(argumentsParser: $argumentsParserStub);

        $actual = $factory->make();

        $this->assertInstanceOf(TestSuite::class, $actual);
    }

    #[Test]
    public function interpretsAllNonPrefixedArgumentsAsPaths(): void
    {
        $expected = [__DIR__ . '/../Fixtures/Basic/PATH0', __DIR__ . '/../Fixtures/Basic/PATH1', __DIR__ . '/../Fixtures/Basic/PATH2'];
        $argumentsParserStub = new ArgumentsParserStub([], $expected);
        $factory = $this->createTestSuiteFactory(argumentsParser: $argumentsParserStub);

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
        $factory = $this->createTestSuiteFactory(argumentsParser: $argumentsParserStub);

        $actual = $factory->make();

        $this->assertEqualsCanonicalizing($expected, $actual->paths());
    }

    #[Test]
    public function failsWhenPathsIsEmpty(): void
    {
        $this->expectExceptionObject(new MissingPathException('No test path specified. Aborting.'));

        $factory = $this->createTestSuiteFactory();

        $factory->make();
    }

    #[Test]
    public function defaultConfigForCachingIsTrue(): void
    {
        $argumentsParserStub = new ArgumentsParserStub([], [__DIR__ . '/../Fixtures/Basic']);
        $factory = $this->createTestSuiteFactory(argumentsParser: $argumentsParserStub);

        $actual = $factory->make();

        $this->assertTrue($actual->cache());
    }

    #[Test]
    public function defaultConcurrencyIsEqualToTheLogicalCoreCountOfTheMachine(): void
    {
        $expected = 123456;
        $argumentsParserStub = new ArgumentsParserStub([], [__DIR__ . '/../Fixtures/Basic']);
        $coreCounterStub = $this->createConfiguredStub(CoreCounter::class, ['count' => $expected]);
        $factory = $this->createTestSuiteFactory(
            argumentsParser: $argumentsParserStub,
            coreCounter: $coreCounterStub,
        );

        $actual = $factory->make();

        $this->assertSame($expected, $actual->concurrency());
    }

    #[Test]
    #[DataProvider('provideConcurrency')]
    public function concurrencyCanBeConfiguredAndIsClampedBetween1AndLogicalCpuCount(int $input, int $expected): void
    {
        $argumentsParserStub = new ArgumentsParserStub(['concurrency' => "{$input}"], [__DIR__ . '/../Fixtures/Basic']);
        $coreCounterStub = $this->createConfiguredStub(CoreCounter::class, ['count' => 500]);
        $factory = $this->createTestSuiteFactory(
            argumentsParser: $argumentsParserStub,
            coreCounter: $coreCounterStub,
        );

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
        $factory = $this->createTestSuiteFactory(argumentsParser: $argumentsParserStub);

        $actual = $factory->make();

        $this->assertFalse($actual->cache());
    }

    #[Test]
    public function defaultConfigForRunnerModeIsAsync(): void
    {
        $argumentsParserStub = new ArgumentsParserStub([], [__DIR__ . '/../Fixtures/Basic']);
        $factory = $this->createTestSuiteFactory(argumentsParser: $argumentsParserStub);

        $actual = $factory->make([]);

        $this->assertSame(TestRunnerMode::Async, $actual->testRunnerMode());
    }

    #[Test]
    public function configForRunnerModeCanBeSetToLinear(): void
    {
        $argumentsParserStub = new ArgumentsParserStub(['debug' => null], [__DIR__ . '/../Fixtures/Basic']);
        $factory = $this->createTestSuiteFactory(argumentsParser: $argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame(TestRunnerMode::Linear, $actual->testRunnerMode());
    }

    #[Test]
    public function failsWhenProvidedPathDoesNotExist(): void
    {
        $this->expectExceptionObject(new InvalidPathException("Provided path `AAA` does not exist"));

        $factory = $this->createTestSuiteFactory(argumentsParser: new ArgumentsParserStub([], ['AAA']));

        $factory->make();
    }

    #[Test]
    public function addsValidDirectoryContentsRecursivelyToPaths(): void
    {
        $argumentsParserStub = new ArgumentsParserStub([], [__DIR__ . '/../Fixtures/Basic']);
        $factory = $this->createTestSuiteFactory(argumentsParser: $argumentsParserStub);

        $actual = $factory->make();

        $this->assertCount(6, $actual->paths());
    }

    #[Test]
    public function defaultStopOnCharacteristicIsNothing(): void
    {
        $argumentsParserStub = new ArgumentsParserStub([], [__DIR__ . '/../Fixtures/Basic']);
        $factory = $this->createTestSuiteFactory(argumentsParser: $argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame(StopOnCharacteristic::Nothing, $actual->stopOnCharacteristic());
    }

    #[Test]
    #[DataProvider('provideStopOnOptions')]
    public function stopOnCharacteristicCanBeChanged(array $options, StopOnCharacteristic $expected): void
    {
        $argumentsParserStub = new ArgumentsParserStub($options, [__DIR__ . '/../Fixtures/Basic']);
        $factory = $this->createTestSuiteFactory(argumentsParser: $argumentsParserStub);

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

    #[Test]
    public function defaultTimeoutValueIsSet(): void
    {
        $factory = $this->createTestSuiteFactory(
            argumentsParser: new ArgumentsParserStub([], [__DIR__ . '/../Fixtures/Basic']),
        );

        $actual = $factory->make();

        $this->assertSame(GenericTestSuiteFactory::DEFAULT_TIMEOUT, $actual->timeout());
    }

    #[Test]
    public function timeoutValueCanBeSet(): void
    {
        $factory = $this->createTestSuiteFactory(
            argumentsParser: new ArgumentsParserStub(['timeout' => '123'], [__DIR__ . '/../Fixtures/Basic']),
        );

        $actual = $factory->make();

        $this->assertSame(123, $actual->timeout());
    }

    #[Test]
    #[DataProvider('provideInvalidTimeoutValues')]
    public function anInvalidTimeoutValueResultsInTheUsageOfTheDefaultValue(string $timeout): void
    {
        $factory = $this->createTestSuiteFactory(
            argumentsParser: new ArgumentsParserStub(['timeout' => $timeout], [__DIR__ . '/../Fixtures/Basic']),
        );

        $actual = $factory->make();

        $this->assertSame(GenericTestSuiteFactory::DEFAULT_TIMEOUT, $actual->timeout());
    }

    #[Test]
    #[DataProvider('provideInvalidTimeoutValues')]
    public function informsAboutInvalidTimeoutValue(string $timeout): void
    {
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->once())->method('writeln')
            ->with('[NOTICE] Invalid timeout value provided - defaulting to 10 seconds');
        $printerMock->expects($this->once())->method('newLine');

        $factory = $this->createTestSuiteFactory(
            argumentsParser: new ArgumentsParserStub(['timeout' => $timeout], [__DIR__ . '/../Fixtures/Basic']),
            printer: $printerMock,
        );

        $factory->make();
    }

    private function consecutive(mixed ...$arguments): callable
    {
        $count = 0;
        return static fn(mixed $argument): bool => $argument === $arguments[$count++];
    }

    public static function provideInvalidTimeoutValues(): Generator
    {
        yield 'zero' => ['0'];
        yield 'negative' => ['-5'];
        yield 'letter' => ['a'];
    }
}
