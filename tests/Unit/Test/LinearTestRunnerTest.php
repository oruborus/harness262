<?php

declare(strict_types=1);

namespace Tests\Unit\Test;

use Oru\Harness\Assertion\Exception\AssertionFailedException;
use Oru\Harness\Contracts\Assertion;
use Oru\Harness\Contracts\AssertionFactory;
use Oru\Harness\Contracts\Facade;
use Oru\Harness\Contracts\Frontmatter;
use Oru\Harness\Contracts\FrontmatterInclude;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Test\GenericTestResult;
use Oru\Harness\Test\LinearTestRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(LinearTestRunner::class)]
#[UsesClass(GenericTestResult::class)]
final class LinearTestRunnerTest extends TestCase
{
    #[Test]
    public function skipsTestExecutionWhenRequiredFeatureIsNotImplemented(): void
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
                'features' => ['missingFeature', 'supportedFeature1']
            ])
        ]);

        $testRunner->run($config);
        [$actual] = $testRunner->finalize();

        $this->assertSame(TestResultState::Skip, $actual->state());
        $this->assertSame(0, $actual->duration());
    }

    #[Test]
    public function doesNotSkipTestExecutionWhenRequiredFeatureIsNotImplemented(): void
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
            ])
        ]);

        $testRunner->run($config);
        [$actual] = $testRunner->finalize();

        $this->assertNotSame(TestResultState::Skip, $actual->state());
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
            ])
        ]);

        $testRunner->run($config);
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
            'content' => 'CODE'
        ]);

        $testRunner->run($config);
    }

    #[Test]
    public function returnsAnErrorResultContainingTheThrowableWhenEngineThrows(): void
    {
        $expected = new RuntimeException();
        $facadeMock = $this->createMock(Facade::class);
        $facadeMock->expects($this->once())->method('engineRun')->willThrowException($expected);
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->once())->method('step');
        $testRunner = new LinearTestRunner(
            $facadeMock,
            $this->createMock(AssertionFactory::class),
            $printerMock
        );
        $config = $this->createMock(TestConfig::class);

        $testRunner->run($config);
        [$actual] = $testRunner->finalize();

        $this->assertSame(TestResultState::Error, $actual->state());
        $this->assertSame($expected, $actual->throwable());
        $this->assertSame(0, $actual->duration());
    }

    #[Test]
    public function returnsAnFailResultContainingTheAssertionFailedExceptionFromTheAssertion(): void
    {
        $expected = new AssertionFailedException();

        $assertionMock = $this->createMock(Assertion::class);
        $assertionMock->method('assert')->willThrowException($expected);
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->once())->method('step');

        $testRunner = new LinearTestRunner(
            $this->createConfiguredMock(Facade::class, [
                'engineRun' => 'UndefinedValue'
            ]),
            $this->createConfiguredMock(AssertionFactory::class, [
                'make' => $assertionMock
            ]),
            $printerMock
        );
        $config = $this->createMock(TestConfig::class);

        $testRunner->run($config);
        [$actual] = $testRunner->finalize();

        $this->assertSame(TestResultState::Fail, $actual->state());
        $this->assertSame($expected, $actual->throwable());
        $this->assertSame(0, $actual->duration());
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
        $config = $this->createMock(TestConfig::class);

        $testRunner->run($config);
        [$actual] = $testRunner->finalize();

        $this->assertSame(TestResultState::Success, $actual->state());
        $this->assertSame(0, $actual->duration());
    }
}
