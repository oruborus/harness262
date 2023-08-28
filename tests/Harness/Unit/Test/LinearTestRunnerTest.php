<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Test;

use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\EcmaScript\Core\Contracts\Values\UndefinedValue;
use Oru\EcmaScript\Harness\Assertion\Exception\AssertionFailedException;
use Oru\EcmaScript\Harness\Contracts\Assertion;
use Oru\EcmaScript\Harness\Contracts\AssertionFactory;
use Oru\EcmaScript\Harness\Contracts\Frontmatter;
use Oru\EcmaScript\Harness\Contracts\FrontmatterInclude;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestResultState;
use Oru\EcmaScript\Harness\Test\GenericTestResult;
use Oru\EcmaScript\Harness\Test\LinearTestRunner;
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
        $testRunner = new LinearTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'getSupportedFeatures' => ['supportedFeature1', 'supportedFeature2']
            ]),
            $this->createMock(AssertionFactory::class)
        );
        $config = $this->createConfiguredMock(TestConfig::class, [
            'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                'features' => ['missingFeature', 'supportedFeature1']
            ])
        ]);

        $actual = $testRunner->run($config);

        $this->assertSame(TestResultState::Skip, $actual->state());
        $this->assertSame(0, $actual->duration());
    }

    #[Test]
    public function doesNotSkipTestExecutionWhenRequiredFeatureIsNotImplemented(): void
    {
        $testRunner = new LinearTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'getSupportedFeatures' => ['supportedFeature1', 'supportedFeature2']
            ]),
            $this->createMock(AssertionFactory::class)
        );
        $config = $this->createConfiguredMock(TestConfig::class, [
            'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                'features' => ['supportedFeature1', 'supportedFeature2']
            ])
        ]);

        $actual = $testRunner->run($config);

        $this->assertNotSame(TestResultState::Skip, $actual->state());
    }

    #[Test]
    public function addsIncludesFromConfigToEngine(): void
    {
        $engineMock = $this->createMock(Engine::class);
        $engineMock->expects($this->exactly(2))->method('addFiles')->willReturnMap(
            [FrontmatterInclude::assert->value],
            [FrontmatterInclude::sta->value]
        );
        $engineMock->method('run')->willReturn($this->createMock(UndefinedValue::class));
        $testRunner = new LinearTestRunner(
            $engineMock,
            $this->createMock(AssertionFactory::class)
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
        $engineMock = $this->createMock(Engine::class);
        $engineMock->expects($this->once())->method('addCode')->with('CODE');
        $engineMock->method('run')->willReturn($this->createMock(UndefinedValue::class));
        $testRunner = new LinearTestRunner(
            $engineMock,
            $this->createMock(AssertionFactory::class)
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
        $engineMock = $this->createMock(Engine::class);
        $engineMock->expects($this->once())->method('run')->willThrowException($expected);
        $testRunner = new LinearTestRunner(
            $engineMock,
            $this->createMock(AssertionFactory::class)
        );
        $config = $this->createMock(TestConfig::class);

        $actual = $testRunner->run($config);

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

        $testRunner = new LinearTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'run' => $this->createMock(UndefinedValue::class)
            ]),
            $this->createConfiguredMock(AssertionFactory::class, [
                'make' => $assertionMock
            ])
        );
        $config = $this->createMock(TestConfig::class);

        $actual = $testRunner->run($config);

        $this->assertSame(TestResultState::Fail, $actual->state());
        $this->assertSame($expected, $actual->throwable());
        $this->assertSame(0, $actual->duration());
    }

    #[Test]
    public function returnsAnSuccessResultWhenNothingFails(): void
    {
        $testRunner = new LinearTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'run' => $this->createMock(UndefinedValue::class)
            ]),
            $this->createMock(AssertionFactory::class)
        );
        $config = $this->createMock(TestConfig::class);

        $actual = $testRunner->run($config);

        $this->assertSame(TestResultState::Success, $actual->state());
        $this->assertSame(0, $actual->duration());
    }
}
