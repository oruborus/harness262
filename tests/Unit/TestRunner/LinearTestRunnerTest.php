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
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Contracts\TestSuiteConfig;
use Oru\Harness\TestRunner\Exception\StopOnCharacteristicMetException;
use Oru\Harness\TestRunner\GenericTestResult;
use Oru\Harness\TestRunner\LinearTestRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

use function array_map;

#[CoversClass(LinearTestRunner::class)]
#[UsesClass(GenericTestResult::class)]
final class LinearTestRunnerTest extends TestCase
{
    #[Test]
    public function skipsTestExecutionWhenRequiredFeatureIsNotImplemented(): void
    {
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->exactly(2))->method('step');
        $facadeStub = $this->createStub(Facade::class);
        $facadeStub->method('engineSupportedFeatures')->willReturn(['supportedFeature1', 'supportedFeature2']);
        $facadeStub->method('engineRun')->willThrowException($this->createStub(Throwable::class));


        $testRunner = new LinearTestRunner(
            $facadeStub,
            $this->createMock(AssertionFactory::class),
            $printerMock
        );
        $config = $this->createConfiguredMock(TestConfig::class, [
            'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                'features' => ['missingFeature', 'supportedFeature1']
            ]),
            'testSuiteConfig' => $this->createConfiguredStub(TestSuiteConfig::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner->add($config);
        $testRunner->add($config);
        $actual = $testRunner->run();

        $this->assertCount(2, $actual);
        $this->assertSame(TestResultState::Skip, $actual[0]->state());
        $this->assertSame(0, $actual[0]->duration());
    }

    #[Test]
    public function doesNotSkipTestExecutionWhenRequiredFeatureIsImplemented(): void
    {
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->once())->method('step');
        $testRunner = new LinearTestRunner(
            $this->createConfiguredMock(Facade::class, [
                'engineSupportedFeatures' => ['supportedFeature1', 'supportedFeature2']
            ]),
            $this->createMock(AssertionFactory::class),
            $printerMock
        );
        $config = $this->createConfiguredMock(TestConfig::class, [
            'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                'features' => ['supportedFeature1', 'supportedFeature2']
            ]),
            'testSuiteConfig' => $this->createConfiguredStub(TestSuiteConfig::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner->add($config);
        [$actual] = $testRunner->run();

        $this->assertNotSame(TestResultState::Skip, $actual->state());
    }

    #[Test]
    public function initializesFacadeBeforeRunningTest(): void
    {
        $facadeMock = $this->createMock(Facade::class);
        $facadeMock->expects($this->once())->method('initialize');
        $testRunner = new LinearTestRunner(
            $facadeMock,
            $this->createMock(AssertionFactory::class),
            $this->createMock(Printer::class)
        );
        $config = $this->createConfiguredStub(TestConfig::class, [
            'testSuiteConfig' => $this->createConfiguredStub(TestSuiteConfig::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner->add($config);
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
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->once())->method('step');
        $facadeMock->method('engineRun')->willReturn('UndefinedValue');
        $testRunner = new LinearTestRunner(
            $facadeMock,
            $this->createMock(AssertionFactory::class),
            $printerMock
        );
        $config = $this->createConfiguredMock(TestConfig::class, [
            'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                'includes' => [FrontmatterInclude::assert, FrontmatterInclude::sta]
            ]),
            'testSuiteConfig' => $this->createConfiguredStub(TestSuiteConfig::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner->add($config);
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
        $testRunner = new LinearTestRunner(
            $facadeMock,
            $this->createMock(AssertionFactory::class),
            $printerMock
        );
        $config = $this->createConfiguredMock(TestConfig::class, [
            'content' => 'CODE',
            'testSuiteConfig' => $this->createConfiguredStub(TestSuiteConfig::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner->add($config);
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
        $testRunner = new LinearTestRunner(
            $facadeMock,
            $this->createConfiguredStub(AssertionFactory::class, ['make' => $assertionStub]),
            $printerMock
        );
        $config = $this->createConfiguredStub(TestConfig::class, [
            'testSuiteConfig' => $this->createConfiguredStub(TestSuiteConfig::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner->add($config);
        $testRunner->add($config);
        $actual = $testRunner->run();

        $this->assertCount(2, $actual);
        $this->assertSame(TestResultState::Error, $actual[0]->state());
        $this->assertSame($expected, $actual[0]->throwable());
        $this->assertSame(0, $actual[0]->duration());
    }

    #[Test]
    public function returnsAFailResultContainingTheAssertionFailedExceptionFromTheAssertion(): void
    {
        $expected = new AssertionFailedException();

        $assertionMock = $this->createMock(Assertion::class);
        $assertionMock->method('assert')->willThrowException($expected);
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->exactly(2))->method('step');

        $testRunner = new LinearTestRunner(
            $this->createConfiguredMock(Facade::class, [
                'engineRun' => 'UndefinedValue'
            ]),
            $this->createConfiguredMock(AssertionFactory::class, [
                'make' => $assertionMock
            ]),
            $printerMock
        );
        $config = $this->createConfiguredStub(TestConfig::class, [
            'testSuiteConfig' => $this->createConfiguredStub(TestSuiteConfig::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner->add($config);
        $testRunner->add($config);
        $actual = $testRunner->run();

        $this->assertCount(2, $actual);
        $this->assertSame(TestResultState::Fail, $actual[0]->state());
        $this->assertSame($expected, $actual[0]->throwable());
        $this->assertSame(0, $actual[0]->duration());
    }

    #[Test]
    public function returnsAnErrorResultContainingTheThrowableFromTheAssertion(): void
    {
        $expected = new Exception();

        $assertionMock = $this->createMock(Assertion::class);
        $assertionMock->method('assert')->willThrowException($expected);
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->exactly(2))->method('step');

        $testRunner = new LinearTestRunner(
            $this->createConfiguredMock(Facade::class, [
                'engineRun' => 'UndefinedValue'
            ]),
            $this->createConfiguredMock(AssertionFactory::class, [
                'make' => $assertionMock
            ]),
            $printerMock
        );
        $config = $this->createConfiguredStub(TestConfig::class, [
            'testSuiteConfig' => $this->createConfiguredStub(TestSuiteConfig::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner->add($config);
        $testRunner->add($config);
        $actual = $testRunner->run();

        $this->assertCount(2, $actual);
        $this->assertSame(TestResultState::Error, $actual[0]->state());
        $this->assertSame($expected, $actual[0]->throwable());
        $this->assertSame(0, $actual[0]->duration());
    }

    #[Test]
    public function returnsAnSuccessResultWhenNothingFails(): void
    {
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->once())->method('step');
        $testRunner = new LinearTestRunner(
            $this->createConfiguredMock(Facade::class, [
                'engineRun' => 'UndefinedValue'
            ]),
            $this->createMock(AssertionFactory::class),
            $printerMock
        );
        $config = $this->createConfiguredStub(TestConfig::class, [
            'testSuiteConfig' => $this->createConfiguredStub(TestSuiteConfig::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner->add($config);
        [$actual] = $testRunner->run();

        $this->assertSame(TestResultState::Success, $actual->state());
        $this->assertSame(0, $actual->duration());
    }

    #[Test]
    public function capturesAndPerformsAssertionOnEngineOutputForAsyncTest(): void
    {
        $expected = 'TEST OUTPUT';
        $facadeMock = $this->createMock(Facade::class);
        $facadeMock->method('engineRun')->willReturnCallback(static function () use ($expected): void {
            echo $expected;
        });
        $facadeMock->method('isNormalCompletion')->willReturn(true);
        $assertionMock = $this->createMock(Assertion::class);
        $assertionMock->expects($this->once())->method('assert')->with($expected);
        $assertionFactoryMock = $this->createConfiguredMock(AssertionFactory::class, [
            'make' => $assertionMock
        ]);

        $testRunner = new LinearTestRunner(
            $facadeMock,
            $assertionFactoryMock,
            $this->createMock(Printer::class)
        );
        $config = $this->createConfiguredMock(TestConfig::class, [
            'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                'flags' => [FrontmatterFlag::async]
            ]),
            'testSuiteConfig' => $this->createConfiguredStub(TestSuiteConfig::class, [
                'stopOnCharacteristic' => StopOnCharacteristic::Nothing
            ])
        ]);

        $testRunner->add($config);
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
        $testConfigStub = $this->createConfiguredStub(TestConfig::class, [
            'testSuiteConfig' => $this->createConfiguredStub(TestSuiteConfig::class, [
                'stopOnCharacteristic' => $stopOnCharacteristic
            ])
        ]);
        $testRunner = new LinearTestRunner(
            $facadeStub,
            $this->createStub(AssertionFactory::class),
            $this->createStub(Printer::class),
            $stopOnCharacteristic
        );
        $testRunner->add($testConfigStub);
        $testRunner->add($testConfigStub);
        $testRunner->add($testConfigStub);

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
        $testConfigStub = $this->createConfiguredStub(TestConfig::class, [
            'testSuiteConfig' => $this->createConfiguredStub(TestSuiteConfig::class, [
                'stopOnCharacteristic' => $stopOnCharacteristic
            ])
        ]);
        $testRunner = new LinearTestRunner(
            $this->createStub(Facade::class),
            $this->createConfiguredStub(AssertionFactory::class, ['make' => $assertionStub]),
            $this->createStub(Printer::class),
            $stopOnCharacteristic
        );
        $testRunner->add($testConfigStub);
        $testRunner->add($testConfigStub);
        $testRunner->add($testConfigStub);
        $testRunner->add($testConfigStub);

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
