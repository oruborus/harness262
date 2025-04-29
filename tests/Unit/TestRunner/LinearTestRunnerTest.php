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

use Exception;
use Generator;
use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\EcmaScript\Core\Contracts\Values\AbruptCompletion;
use Oru\EcmaScript\Core\Contracts\Values\UnusedValue;
use Oru\Harness\Assertion\Exception\AssertionFailedException;
use Oru\Harness\Assertion\Exception\EngineException;
use Oru\Harness\Contracts\Assertion;
use Oru\Harness\Contracts\AssertionFactory;
use Oru\Harness\Contracts\EngineFactory;
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
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use RuntimeException;
use Tests\Unit\CanCreateTestResultFactoryStub;
use Throwable;

use function array_map;

#[CoversClass(LinearTestRunner::class)]
#[UsesClass(GenericTestResult::class)]
final class LinearTestRunnerTest extends PHPUnitTestCase
{
    use CanCreateTestResultFactoryStub;

    #[Test]
    public function skipsTestExecutionWhenRequiredFeatureIsNotImplemented(): void
    {
        $engineStub = $this->createStub(Engine::class);
        $engineStub->method('getSupportedFeatures')->willReturn(['supportedFeature1', 'supportedFeature2']);
        $engineStub->method('run')->willThrowException($this->createStub(Throwable::class));
        $engineFactoryStub = $this->createConfiguredStub(EngineFactory::class, ['make' => $engineStub]);
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
            $engineFactoryStub,
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
            $this->createConfiguredStub(EngineFactory::class, [
                'make' => $this->createConfiguredStub(Engine::class, [
                    'getSupportedFeatures' => ['supportedFeature1', 'supportedFeature2'],
                    'run' => $this->createStub(UnusedValue::class),
                ])
            ]),
            $this->createStub(AssertionFactory::class),
            $printerMock,
            $testResultFactoryMock,
        );

        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    public function addsIncludesFromConfigToEngine(): void
    {
        $engineMock = $this->createMock(Engine::class);
        $engineMock->expects($this->exactly(2))->method('addFiles')->willReturnMap([
            [FrontmatterInclude::assert->value],
            [FrontmatterInclude::sta->value]
        ]);
        $engineMock->method('run')->willReturn($this->createStub(UnusedValue::class));
        $engineFactoryStub = $this->createConfiguredStub(EngineFactory::class, ['make' => $engineMock]);
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
            $engineFactoryStub,
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
        $engineMock = $this->createMock(Engine::class);
        $engineMock->expects($this->once())->method('addCode')->with('CODE');
        $engineMock->method('run')->willReturn($this->createStub(UnusedValue::class));
        $engineFactoryStub = $this->createConfiguredStub(EngineFactory::class, ['make' => $engineMock]);
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
            $engineFactoryStub,
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
        $engineMock = $this->createMock(Engine::class);
        $engineMock->expects($this->exactly(2))->method('run')->willThrowException($expected);
        $engineFactoryStub = $this->createConfiguredStub(EngineFactory::class, ['make' => $engineMock]);
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
            $engineFactoryStub,
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
            $this->createConfiguredStub(EngineFactory::class, [
                'make' => $this->createConfiguredStub(Engine::class, [
                    'run' => $this->createStub(UnusedValue::class),
                ])
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
            $this->createConfiguredStub(EngineFactory::class, [
                'make' => $this->createConfiguredStub(Engine::class, [
                    'run' => $this->createStub(UnusedValue::class),
                ])
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
            $this->createConfiguredStub(EngineFactory::class, [
                'make' => $this->createConfiguredStub(Engine::class, [
                    'run' => $this->createStub(UnusedValue::class),
                ])
            ]),
            $this->createStub(AssertionFactory::class),
            $printerMock,
            $testResultFactoryMock,
        );

        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    public function capturesAndPerformsAssertionOnEngineOutputForAsyncTestWhenTestCaseReturnsANormalCompletion(): void
    {
        $expected = 'TEST OUTPUT';
        $engineStub = $this->createStub(Engine::class);
        $engineStub->method('run')->willReturnCallback(function () use ($expected): UnusedValue {
            echo $expected;

            return $this->createStub(UnusedValue::class);
        });
        $engineFactoryStub = $this->createConfiguredStub(EngineFactory::class, ['make' => $engineStub]);
        $assertionMock = $this->createMock(Assertion::class);
        $assertionMock->expects($this->once())->method('assert')->with($this->identicalTo($expected));
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
            $engineFactoryStub,
            $assertionFactoryMock,
            $this->createStub(Printer::class),
            $testResultFactoryStub,
        );

        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    public function capturesAndPerformsAssertionOnEngineOutputForAsyncTestWhenTestCaseReturnsAnAbruptCompletion(): void
    {
        $expected = $this->createStub(AbruptCompletion::class);
        $engineStub = $this->createStub(Engine::class);
        $engineStub->method('run')->willReturnCallback(static function () use ($expected): mixed {
            echo 'SOME OUTPUT';

            return $expected;
        });
        $engineFactoryStub = $this->createConfiguredStub(EngineFactory::class, ['make' => $engineStub]);
        $assertionMock = $this->createMock(Assertion::class);
        $assertionMock->expects($this->once())->method('assert')->with($this->identicalTo($expected));
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
            $engineFactoryStub,
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
        $engineStub = $this->createStub(Engine::class);
        $engineStub->method('run')->willReturnCallback(function (): UnusedValue {
            static $callCount = 0;
            $callCount++;

            if ($callCount === 2) {
                throw $this->createStub(Throwable::class);
            }

            return $this->createStub(UnusedValue::class);
        });
        $engineFactoryStub = $this->createConfiguredStub(EngineFactory::class, ['make' => $engineStub]);
        $testCaseStub = $this->createConfiguredStub(TestCase::class, [
            'testSuite' => $this->createConfiguredStub(TestSuite::class, [
                'stopOnCharacteristic' => $stopOnCharacteristic
            ])
        ]);
        $testRunner = new LinearTestRunner(
            $engineFactoryStub,
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
            $this->createConfiguredStub(EngineFactory::class, [
                'make' => $this->createConfiguredStub(Engine::class, [
                    'run' => $this->createStub(UnusedValue::class)
                ])
            ]),
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
}
