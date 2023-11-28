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

namespace Tests\Unit\TestRunner;

use Closure;
use Exception;
use Generator;
use Oru\Harness\Assertion\Exception\AssertionFailedException;
use Oru\Harness\Assertion\Exception\EngineException;
use Oru\Harness\Contracts\Assertion;
use Oru\Harness\Contracts\AssertionFactory;
use Oru\Harness\Contracts\Facade;
use Oru\Harness\Contracts\Frontmatter;
use Oru\Harness\Contracts\FrontmatterFlag;
use Oru\Harness\Contracts\FrontmatterInclude;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultFactory;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Contracts\TestSuite;
use Oru\Harness\TestResult\GenericTestResult;
use Oru\Harness\TestRunner\Exception\StopOnCharacteristicMetException;
use Oru\Harness\TestRunner\LinearTestRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use RuntimeException;
use Throwable;

use function array_map;

#[CoversClass(LinearTestRunner::class)]
#[UsesClass(GenericTestResult::class)]
final class LinearTestRunnerTest extends PHPUnitTestCase
{
    #[Test]
    public function skipsTestExecutionWhenRequiredFeatureIsNotImplemented(): void
    {
        $facadeStub = $this->createStub(Facade::class);
        $facadeStub->method('engineSupportedFeatures')->willReturn(['supportedFeature1', 'supportedFeature2']);
        $facadeStub->method('engineRun')->willThrowException($this->createStub(Throwable::class));
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->exactly(2))->method('step');
        $testResultStub = $this->createConfiguredStub(TestResult::class, ['state' => TestResultState::Skip]);
        $testResultFactoryMock = $this->createMock(TestResultFactory::class);
        $testResultFactoryMock->expects($this->exactly(2))->method('makeSkipped')->willReturn($testResultStub);
        $testCaseStub = $this->createConfiguredStub(TestCase::class, [
            'frontmatter' => $this->createConfiguredStub(Frontmatter::class, [
                'features' => ['missingFeature', 'supportedFeature1']
            ]),
            'testSuite' => $this->createConfiguredStub(TestSuite::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner = new LinearTestRunner(
            $facadeStub,
            $this->createMock(AssertionFactory::class),
            $printerMock,
            $testResultFactoryMock,
        );

        $testRunner->add($testCaseStub);
        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    public function doesNotSkipTestExecutionWhenRequiredFeatureIsImplemented(): void
    {
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->once())->method('step');
        $testResultStub = $this->createConfiguredStub(TestResult::class, ['state' => TestResultState::Success]);
        $testResultFactoryMock = $this->createMock(TestResultFactory::class);
        $testResultFactoryMock->expects($this->once())->method('makeSuccessful')->with($this->anything(), $this->anything(), 0)->willReturn($testResultStub);
        $testCaseStub = $this->createConfiguredStub(TestCase::class, [
            'frontmatter' => $this->createConfiguredStub(Frontmatter::class, [
                'features' => ['supportedFeature1', 'supportedFeature2']
            ]),
            'testSuite' => $this->createConfiguredStub(TestSuite::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner = new LinearTestRunner(
            $this->createConfiguredStub(Facade::class, [
                'engineSupportedFeatures' => ['supportedFeature1', 'supportedFeature2']
            ]),
            $this->createStub(AssertionFactory::class),
            $printerMock,
            $testResultFactoryMock,
        );

        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    public function initializesFacadeBeforeRunningTest(): void
    {
        $facadeMock = $this->createMock(Facade::class);
        $facadeMock->expects($this->once())->method('initialize');
        $testResultFactoryStub = $this->createTestResultFactoryStub();
        $testCaseStub = $this->createConfiguredStub(TestCase::class, [
            'testSuite' => $this->createConfiguredStub(TestSuite::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner = new LinearTestRunner(
            $facadeMock,
            $this->createStub(AssertionFactory::class),
            $this->createStub(Printer::class),
            $testResultFactoryStub
        );

        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    public function addsIncludesFromConfigToEngine(): void
    {
        $facadeMock = $this->createMock(Facade::class);
        $facadeMock->expects($this->exactly(2))->method('engineAddFiles')->willReturnMap(
            [FrontmatterInclude::assert->value],
            [FrontmatterInclude::sta->value]
        );
        $facadeMock->method('engineRun')->willReturn('UndefinedValue');
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->once())->method('step');
        $testResultFactoryStub = $this->createTestResultFactoryStub();
        $testCaseStub = $this->createConfiguredStub(TestCase::class, [
            'frontmatter' => $this->createConfiguredStub(Frontmatter::class, [
                'includes' => [FrontmatterInclude::assert, FrontmatterInclude::sta]
            ]),
            'testSuite' => $this->createConfiguredStub(TestSuite::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner = new LinearTestRunner(
            $facadeMock,
            $this->createStub(AssertionFactory::class),
            $printerMock,
            $testResultFactoryStub,
        );

        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    public function addsCodeFromConfigToEngine(): void
    {
        $facadeMock = $this->createMock(Facade::class);
        $facadeMock->expects($this->once())->method('engineAddCode')->with('CODE');
        $facadeMock->method('engineRun')->willReturn('UndefinedValue');
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->once())->method('step');
        $testResultFactoryStub = $this->createTestResultFactoryStub();
        $testCaseStub = $this->createConfiguredStub(TestCase::class, [
            'content' => 'CODE',
            'testSuite' => $this->createConfiguredStub(TestSuite::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner = new LinearTestRunner(
            $facadeMock,
            $this->createStub(AssertionFactory::class),
            $printerMock,
            $testResultFactoryStub,
        );

        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    public function returnsAnErrorResultContainingTheThrowableWhenEngineThrows(): void
    {
        $expected = new RuntimeException();
        $facadeMock = $this->createMock(Facade::class);
        $facadeMock->expects($this->exactly(2))->method('engineRun')->willThrowException($expected);
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->exactly(2))->method('step');
        $assertionStub = $this->createStub(Assertion::class);
        $assertionStub->method('assert')->willThrowException($this->createStub(Throwable::class));
        $assertionFactoryStub = $this->createConfiguredStub(AssertionFactory::class, ['make' => $assertionStub]);
        $testResultStub = $this->createConfiguredStub(TestResult::class, ['state' => TestResultState::Error]);
        $testResultFactoryMock = $this->createMock(TestResultFactory::class);
        $testResultFactoryMock->expects($this->exactly(2))->method('makeErrored')->with($this->anything(), $this->anything(), 0, $expected)->willReturn($testResultStub);
        $testCaseStub = $this->createConfiguredStub(TestCase::class, [
            'testSuite' => $this->createConfiguredStub(TestSuite::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner = new LinearTestRunner(
            $facadeMock,
            $assertionFactoryStub,
            $printerMock,
            $testResultFactoryMock
        );

        $testRunner->add($testCaseStub);
        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    public function returnsAFailResultContainingTheAssertionFailedExceptionFromTheAssertion(): void
    {
        $expected = new AssertionFailedException();
        $assertionStub = $this->createStub(Assertion::class);
        $assertionStub->method('assert')->willThrowException($expected);
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->exactly(2))->method('step');
        $testResultStub = $this->createConfiguredStub(TestResult::class, ['state' => TestResultState::Fail]);
        $testResultFactoryMock = $this->createMock(TestResultFactory::class);
        $testResultFactoryMock->expects($this->exactly(2))->method('makeFailed')->with($this->anything(), $this->anything(), 0, $expected)->willReturn($testResultStub);
        $testCaseStub = $this->createConfiguredStub(TestCase::class, [
            'testSuite' => $this->createConfiguredStub(TestSuite::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner = new LinearTestRunner(
            $this->createConfiguredMock(Facade::class, [
                'engineRun' => 'UndefinedValue'
            ]),
            $this->createConfiguredMock(AssertionFactory::class, [
                'make' => $assertionStub
            ]),
            $printerMock,
            $testResultFactoryMock
        );

        $testRunner->add($testCaseStub);
        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    public function returnsAnErrorResultContainingTheThrowableFromTheAssertion(): void
    {
        $expected = new Exception();
        $assertionStub = $this->createStub(Assertion::class);
        $assertionStub->method('assert')->willThrowException($expected);
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->exactly(2))->method('step');
        $testResultStub = $this->createConfiguredStub(TestResult::class, ['state' => TestResultState::Error]);
        $testResultFactoryMock = $this->createMock(TestResultFactory::class);
        $testResultFactoryMock->expects($this->exactly(2))->method('makeErrored')->with($this->anything(), $this->anything(), 0, $expected)->willReturn($testResultStub);
        $testCaseStub = $this->createConfiguredStub(TestCase::class, [
            'testSuite' => $this->createConfiguredStub(TestSuite::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner = new LinearTestRunner(
            $this->createConfiguredStub(Facade::class, [
                'engineRun' => 'UndefinedValue'
            ]),
            $this->createConfiguredStub(AssertionFactory::class, [
                'make' => $assertionStub
            ]),
            $printerMock,
            $testResultFactoryMock,
        );

        $testRunner->add($testCaseStub);
        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    public function returnsAnSuccessResultWhenNothingFails(): void
    {
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->once())->method('step');
        $testResultStub = $this->createConfiguredStub(TestResult::class, ['state' => TestResultState::Success]);
        $testResultFactoryMock = $this->createMock(TestResultFactory::class);
        $testResultFactoryMock->expects($this->once())->method('makeSuccessful')->with($this->anything(), $this->anything(), 0)->willReturn($testResultStub);
        $testCaseStub = $this->createConfiguredStub(TestCase::class, [
            'testSuite' => $this->createConfiguredStub(TestSuite::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner = new LinearTestRunner(
            $this->createConfiguredStub(Facade::class, [
                'engineRun' => 'UndefinedValue'
            ]),
            $this->createStub(AssertionFactory::class),
            $printerMock,
            $testResultFactoryMock,
        );

        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    public function capturesAndPerformsAssertionOnEngineOutputForAsyncTest(): void
    {
        $expected = 'TEST OUTPUT';
        $facadeStub = $this->createStub(Facade::class);
        $facadeStub->method('engineRun')->willReturnCallback(static function () use ($expected): void {
            echo $expected;
        });
        $facadeStub->method('isNormalCompletion')->willReturn(true);
        $assertionMock = $this->createMock(Assertion::class);
        $assertionMock->expects($this->once())->method('assert')->with($expected);
        $assertionFactoryMock = $this->createConfiguredMock(AssertionFactory::class, [
            'make' => $assertionMock
        ]);
        $testResultFactoryStub = $this->createTestResultFactoryStub();
        $testCaseStub = $this->createConfiguredStub(TestCase::class, [
            'frontmatter' => $this->createConfiguredStub(Frontmatter::class, [
                'flags' => [FrontmatterFlag::async]
            ]),
            'testSuite' => $this->createConfiguredStub(TestSuite::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner = new LinearTestRunner(
            $facadeStub,
            $assertionFactoryMock,
            $this->createStub(Printer::class),
            $testResultFactoryStub,
        );

        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    #[DataProvider('provideStopOnCharacteristicForEngineDefects')]
    public function stopsTestExecutionWhenEngineThrowsAndAccordingStopOnCharacteristicIsSet(StopOnCharacteristic $stopOnCharacteristic, array $expected): void
    {
        $facadeStub = $this->createStub(Facade::class);
        $facadeStub->method('engineRun')->willReturnCallback(function (): void {
            static $callCount = 0;
            $callCount++;

            if ($callCount === 2) {
                throw $this->createStub(Throwable::class);
            }
        });
        $testCaseStub = $this->createConfiguredStub(TestCase::class, [
            'testSuite' => $this->createConfiguredStub(TestSuite::class, [
                'stopOnCharacteristic' => $stopOnCharacteristic
            ])
        ]);
        $testRunner = new LinearTestRunner(
            $facadeStub,
            $this->createStub(AssertionFactory::class),
            $this->createStub(Printer::class),
            $this->createTestResultFactoryStub(),
        );
        $testRunner->add($testCaseStub);
        $testRunner->add($testCaseStub);
        $testRunner->add($testCaseStub);

        try {
            $testRunner->run();
        } catch (StopOnCharacteristicMetException) {
        }

        $actual = array_map(static fn(TestResult $result): TestResultState => $result->state(), $testRunner->results());

        $this->assertSame($expected, $actual);
    }

    public static function provideStopOnCharacteristicForEngineDefects(): Generator
    {
        yield 'stop on defect'  => [StopOnCharacteristic::Defect, [TestResultState::Success, TestResultState::Error]];
        yield 'stop on error'   => [StopOnCharacteristic::Error,  [TestResultState::Success, TestResultState::Error]];
        yield 'stop on nothing' => [StopOnCharacteristic::Nothing, [TestResultState::Success, TestResultState::Error, TestResultState::Success]];
    }

    #[Test]
    #[DataProvider('provideStopOnCharacteristicForTestCaseDefects')]
    public function willPerformTestsAccordingToProvidedStopOnCharacteristic(StopOnCharacteristic $stopOnCharacteristic, array $order, array $expected): void
    {
        $assertionStub = $this->createStub(Assertion::class);
        $assertionStub->method('assert')->willReturnCallback(function () use ($order): void {
            static $callCount = 0;
            match (++$callCount) {
                $order[0] => throw new AssertionFailedException(),
                $order[1] => throw new EngineException(),
                default => null
            };
        });
        $testCaseStub = $this->createConfiguredStub(TestCase::class, [
            'testSuite' => $this->createConfiguredStub(TestSuite::class, [
                'stopOnCharacteristic' => $stopOnCharacteristic
            ])
        ]);
        $testRunner = new LinearTestRunner(
            $this->createStub(Facade::class),
            $this->createConfiguredStub(AssertionFactory::class, ['make' => $assertionStub]),
            $this->createStub(Printer::class),
            $this->createTestResultFactoryStub(),
        );

        $testRunner->add($testCaseStub);
        $testRunner->add($testCaseStub);
        $testRunner->add($testCaseStub);
        $testRunner->add($testCaseStub);

        try {
            $testRunner->run();
        } catch (StopOnCharacteristicMetException) {
        }

        $actual = array_map(static fn(TestResult $result): TestResultState => $result->state(), $testRunner->results());

        $this->assertSame($expected, $actual);
    }

    public static function provideStopOnCharacteristicForTestCaseDefects(): Generator
    {
        yield 'stop on defect 1'  => [StopOnCharacteristic::Defect, [2, 3], [TestResultState::Success, TestResultState::Fail]];
        yield 'stop on defect 2'  => [StopOnCharacteristic::Defect, [3, 2], [TestResultState::Success, TestResultState::Error]];
        yield 'stop on error'   => [StopOnCharacteristic::Error, [2, 3], [TestResultState::Success, TestResultState::Fail, TestResultState::Error]];
        yield 'stop on failure' => [StopOnCharacteristic::Failure, [2, 3], [TestResultState::Success, TestResultState::Fail]];
        yield 'stop on nothing' => [StopOnCharacteristic::Nothing, [2, 3], [TestResultState::Success, TestResultState::Fail, TestResultState::Error, TestResultState::Success]];
    }

    private function createTestResultFactoryStub(): TestResultFactory
    {
        return new class ($this->createConfiguredStub(...)) implements TestResultFactory {
            public function __construct(
                private Closure $createConfiguredStub,
            ) {}

            /**
             * @template TOriginal
             * @param class-string<TOriginal> $originalClassName
             * @param array<string, mixed> $configuration
             * @return Stub&TOriginal
             */
            private function createConfiguredStub(string $originalClassName, array $configuration): Stub
            {
                return ($this->createConfiguredStub)($originalClassName, $configuration);
            }

            public function makeSkipped(string $path): TestResult
            {
                return $this->createConfiguredStub(TestResult::class, ['state' => TestResultState::Skip]);
            }

            /**
             * @param string[] $usedFiles
             */
            public function makeCached(string $path, array $usedFiles): TestResult
            {
                return $this->createConfiguredStub(TestResult::class, ['state' => TestResultState::Cache]);
            }

            /**
             * @param string[] $usedFiles
             */
            public function makeErrored(string $path, array $usedFiles, int $duration, Throwable $throwable): TestResult
            {
                return $this->createConfiguredStub(TestResult::class, ['state' => TestResultState::Error]);
            }

            /**
             * @param string[] $usedFiles
             */
            public function makeFailed(string $path, array $usedFiles, int $duration, Throwable $throwable): TestResult
            {
                return $this->createConfiguredStub(TestResult::class, ['state' => TestResultState::Fail]);
            }

            /**
             * @param string[] $usedFiles
             */
            public function makeSuccessful(string $path, array $usedFiles, int $duration): TestResult
            {
                return $this->createConfiguredStub(TestResult::class, ['state' => TestResultState::Success]);
            }
        };
    }
}
