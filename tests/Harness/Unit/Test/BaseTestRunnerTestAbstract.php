<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Test;

use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\EcmaScript\Core\Contracts\Values\AbruptCompletion;
use Oru\EcmaScript\Core\Contracts\Values\BooleanValue;
use Oru\EcmaScript\Core\Contracts\Values\NumberValue;
use Oru\EcmaScript\Core\Contracts\Values\ObjectValue;
use Oru\EcmaScript\Core\Contracts\Values\PropertyDescriptor;
use Oru\EcmaScript\Core\Contracts\Values\StringValue;
use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
use Oru\EcmaScript\Core\Contracts\Values\UndefinedValue;
use Oru\EcmaScript\Harness\Contracts\Frontmatter;
use Oru\EcmaScript\Harness\Contracts\FrontmatterNegative;
use Oru\EcmaScript\Harness\Contracts\FrontmatterNegativePhase;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestResultState;
use Oru\EcmaScript\Harness\Test\BaseTestRunner;
use Oru\EcmaScript\Harness\Test\Exception\AssertionFailedException;
use Oru\EcmaScript\Harness\Test\Exception\EngineException;
use Oru\EcmaScript\Harness\Test\GenericTestResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(BaseTestRunner::class)]
#[UsesClass(GenericTestResult::class)]
abstract class BaseTestRunnerTestAbstract extends TestCase
{
    protected abstract function createTestRunner(Engine $engine): BaseTestRunner;

    #[Test]
    public function forPositiveCasesReturnsFailureWhenProvidedValueIsNeitherThrowCompletionNorNormalCompletion(): void
    {
        $testRunner = $this->createTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'run' => $this->createMock(AbruptCompletion::class)
            ])
        );

        $config = $this->createMock(TestConfig::class);

        $actual = $testRunner->run($config);

        $this->assertSame(TestResultState::Fail, $actual->state());
        $this->assertInstanceOf(AssertionFailedException::class, $actual->throwable());
        $this->assertSame('Expected `NormalCompletion`', $actual->throwable()->getMessage());
    }

    #[Test]
    public function forPositiveCasesReturnsFailureWhenProvidedValueIsAThrowCompletionWithNonObjectInside(): void
    {
        $testRunner = $this->createTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'run' => $this->createConfiguredMock(ThrowCompletion::class, [
                    'getValue' => $this->createConfiguredMock(NumberValue::class, [
                        'getValue' => 123.0
                    ])
                ])
            ])
        );

        $config = $this->createMock(TestConfig::class);

        $actual = $testRunner->run($config);

        $this->assertSame(TestResultState::Fail, $actual->state());
        $this->assertInstanceOf(AssertionFailedException::class, $actual->throwable());
        $this->assertSame('123', $actual->throwable()->getMessage());
    }

    #[Test]
    public function forPositiveCasesThrowsExceptionWhenExceptionMessageCannotGetExtracted(): void
    {
        $this->expectExceptionObject(new EngineException('Could not use `Object.[[GetOwnProperty]]()` to retrieve `message`'));

        $exceptionObject = $this->createMock(ObjectValue::class);
        $exceptionObject->method('getOwnProperty')->willThrowException(new RuntimeException());

        $testRunner = $this->createTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'run' => $this->createConfiguredMock(ThrowCompletion::class, [
                    'getValue' => $exceptionObject
                ])
            ])
        );

        $config = $this->createMock(TestConfig::class);

        $testRunner->run($config);
    }

    #[Test]
    public function forPositiveCasesThrowsExceptionWhenExceptionMessageIsUndefined(): void
    {
        $testRunner = $this->createTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'run' => $this->createConfiguredMock(ThrowCompletion::class, [
                    'getValue' =>
                    $this->createConfiguredMock(ObjectValue::class, [
                        'getOwnProperty' => $this->createMock(UndefinedValue::class)
                    ])
                ])
            ])
        );

        $config = $this->createMock(TestConfig::class);

        $actual = $testRunner->run($config);

        $this->assertSame(TestResultState::Fail, $actual->state());
        $this->assertInstanceOf(AssertionFailedException::class, $actual->throwable());
        $this->assertSame('EngineError without message :(', $actual->throwable()->getMessage());
    }

    #[Test]
    public function forPositiveCasesThrowsExceptionWithMessage(): void
    {
        $testRunner = $this->createTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'run' => $this->createConfiguredMock(ThrowCompletion::class, [
                    'getValue' =>
                    $this->createConfiguredMock(ObjectValue::class, [
                        'getOwnProperty' => $this->createConfiguredMock(PropertyDescriptor::class, [
                            'getValue' => $this->createConfiguredMock(StringValue::class, [
                                'getValue' => 'The correct exception message'
                            ])
                        ])
                    ])
                ])
            ])
        );

        $config = $this->createMock(TestConfig::class);

        $actual = $testRunner->run($config);

        $this->assertSame(TestResultState::Fail, $actual->state());
        $this->assertInstanceOf(AssertionFailedException::class, $actual->throwable());
        $this->assertSame('The correct exception message', $actual->throwable()->getMessage());
    }

    #[Test]
    public function forNegativeCasesReturnsFailureWhenProvidedValueIsNotAThrowCompletion(): void
    {
        $testRunner = $this->createTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'run' => $this->createMock(UndefinedValue::class)
            ])
        );

        $config = $this->createConfiguredMock(TestConfig::class, [
            'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                'negative' => $this->createConfiguredMock(FrontmatterNegative::class, [
                    'phase' => FrontmatterNegativePhase::parse,
                    'type' => 'SyntaxError'
                ])
            ])
        ]);

        $actual = $testRunner->run($config);

        $this->assertSame(TestResultState::Fail, $actual->state());
        $this->assertInstanceOf(AssertionFailedException::class, $actual->throwable());
        $this->assertSame('Expected `ThrowCompletion`', $actual->throwable()->getMessage());
    }

    #[Test]
    public function forPositiveCasesReturnsSuccessWhenProvidedValueIsNotAnAbruptCompletion(): void
    {
        $testRunner = $this->createTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'run' => $this->createMock(UndefinedValue::class)
            ])
        );

        $config = $this->createMock(TestConfig::class);

        $actual = $testRunner->run($config);

        $this->assertSame(TestResultState::Success, $actual->state());
    }

    #[Test]
    public function forNegativeCasesReturnsFailureWhenProvidedThrowCompletionDoesNotContainAnObject(): void
    {
        $testRunner = $this->createTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'run' => $this->createConfiguredMock(ThrowCompletion::class, [
                    'getValue' => $this->createMock(UndefinedValue::class)
                ])
            ])
        );

        $config = $this->createConfiguredMock(TestConfig::class, [
            'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                'negative' => $this->createConfiguredMock(FrontmatterNegative::class, [
                    'phase' => FrontmatterNegativePhase::parse,
                    'type' => 'SyntaxError'
                ])
            ])
        ]);

        $actual = $testRunner->run($config);

        $this->assertSame(TestResultState::Fail, $actual->state());
        $this->assertInstanceOf(AssertionFailedException::class, $actual->throwable());
        $this->assertSame('`ThrowCompletion` does not contain an `ObjectValue`', $actual->throwable()->getMessage());
    }

    #[Test]
    public function forNegativeCasesThrowsExceptionWhenConstructorCannotGetExtracted(): void
    {
        $this->expectExceptionObject(new EngineException('Could not use `get()` to retrieve `constructor`'));

        $exceptionObject = $this->createMock(ObjectValue::class);
        $exceptionObject->method('get')->willThrowException(new RuntimeException());

        $testRunner = $this->createTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'run' => $this->createConfiguredMock(ThrowCompletion::class, [
                    'getValue' => $exceptionObject
                ])
            ])
        );

        $config = $this->createConfiguredMock(TestConfig::class, [
            'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                'negative' => $this->createConfiguredMock(FrontmatterNegative::class, [
                    'phase' => FrontmatterNegativePhase::parse,
                    'type' => 'SyntaxError'
                ])
            ])
        ]);

        $testRunner->run($config);
    }

    #[Test]
    public function forNegativeCasesReturnsFailureWhenConstructorIsNotAnObject(): void
    {
        $testRunner = $this->createTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'run' => $this->createConfiguredMock(ThrowCompletion::class, [
                    'getValue' => $this->createConfiguredMock(ObjectValue::class, [
                        'get' => $this->createMock(UndefinedValue::class)
                    ])
                ])
            ])
        );

        $config = $this->createConfiguredMock(TestConfig::class, [
            'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                'negative' => $this->createConfiguredMock(FrontmatterNegative::class, [
                    'phase' => FrontmatterNegativePhase::parse,
                    'type' => 'SyntaxError'
                ])
            ])
        ]);

        $actual = $testRunner->run($config);

        $this->assertSame(TestResultState::Fail, $actual->state());
        $this->assertInstanceOf(AssertionFailedException::class, $actual->throwable());
        $this->assertSame('Constructor value is not an `ObjectValue`', $actual->throwable()->getMessage());
    }

    #[Test]
    public function forNegativeCasesThrowsExceptionWhenConstructorPropertyCheckThrows(): void
    {
        $this->expectExceptionObject(new EngineException('Could not use `hasName()` to to check existence of `name`'));

        $constructorObject = $this->createMock(ObjectValue::class);
        $constructorObject->method('hasProperty')->willThrowException(new RuntimeException());

        $testRunner = $this->createTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'run' => $this->createConfiguredMock(ThrowCompletion::class, [
                    'getValue' => $this->createConfiguredMock(ObjectValue::class, [
                        'get' => $constructorObject
                    ])
                ])
            ])
        );

        $config = $this->createConfiguredMock(TestConfig::class, [
            'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                'negative' => $this->createConfiguredMock(FrontmatterNegative::class, [
                    'phase' => FrontmatterNegativePhase::parse,
                    'type' => 'SyntaxError'
                ])
            ])
        ]);

        $testRunner->run($config);
    }

    #[Test]
    public function forNegativeCasesReturnsFailureWhenConstructorHasNotANameProperty(): void
    {
        $testRunner = $this->createTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'run' => $this->createConfiguredMock(ThrowCompletion::class, [
                    'getValue' => $this->createConfiguredMock(ObjectValue::class, [
                        'get' => $this->createConfiguredMock(ObjectValue::class, [
                            'hasProperty' => $this->createConfiguredMock(BooleanValue::class, [
                                'getValue' => false
                            ])
                        ])
                    ])
                ])
            ])
        );

        $config = $this->createConfiguredMock(TestConfig::class, [
            'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                'negative' => $this->createConfiguredMock(FrontmatterNegative::class, [
                    'phase' => FrontmatterNegativePhase::parse,
                    'type' => 'SyntaxError'
                ])
            ])
        ]);

        $actual = $testRunner->run($config);

        $this->assertSame(TestResultState::Fail, $actual->state());
        $this->assertInstanceOf(AssertionFailedException::class, $actual->throwable());
        $this->assertSame('Constructor does not have a name', $actual->throwable()->getMessage());
    }

    #[Test]
    public function forNegativeCasesThrowsExceptionWhenConstructorNameCannotGetExtracted(): void
    {
        $this->expectExceptionObject(new EngineException('Could not use `get()` to retrieve `constructor.name`'));

        $constructorObject = $this->createMock(ObjectValue::class);
        $constructorObject->method('hasProperty')->willReturn(
            $this->createConfiguredMock(BooleanValue::class, [
                'getValue' => true
            ])
        );
        $constructorObject->method('get')->willThrowException(new RuntimeException());

        $testRunner = $this->createTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'run' => $this->createConfiguredMock(ThrowCompletion::class, [
                    'getValue' => $this->createConfiguredMock(ObjectValue::class, [
                        'get' => $constructorObject
                    ])
                ])
            ])
        );

        $config = $this->createConfiguredMock(TestConfig::class, [
            'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                'negative' => $this->createConfiguredMock(FrontmatterNegative::class, [
                    'phase' => FrontmatterNegativePhase::parse,
                    'type' => 'SyntaxError'
                ])
            ])
        ]);

        $testRunner->run($config);
    }

    #[Test]
    public function forNegativeCasesReturnsFailureWhenConstructorNameDoesNotMatch(): void
    {
        $testRunner = $this->createTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'run' => $this->createConfiguredMock(ThrowCompletion::class, [
                    'getValue' => $this->createConfiguredMock(ObjectValue::class, [
                        'get' => $this->createConfiguredMock(ObjectValue::class, [
                            'hasProperty' => $this->createConfiguredMock(BooleanValue::class, [
                                'getValue' => true
                            ]),
                            'get' => $this->createConfiguredMock(StringValue::class, [
                                'getValue' => 'WRONG',
                                '__toString' => 'WRONG',
                            ]),

                        ])
                    ])
                ])
            ])
        );

        $config = $this->createConfiguredMock(TestConfig::class, [
            'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                'negative' => $this->createConfiguredMock(FrontmatterNegative::class, [
                    'phase' => FrontmatterNegativePhase::parse,
                    'type' => 'SyntaxError'
                ])
            ])
        ]);

        $actual = $testRunner->run($config);

        $this->assertSame(TestResultState::Fail, $actual->state());
        $this->assertInstanceOf(AssertionFailedException::class, $actual->throwable());
        $this->assertSame('Expected `SyntaxError` but got `WRONG`', $actual->throwable()->getMessage());
    }

    #[Test]
    public function forNegativeCasesReturnsSuccessWhenConstructorNameMatches(): void
    {
        $testRunner = $this->createTestRunner(
            $this->createConfiguredMock(Engine::class, [
                'run' => $this->createConfiguredMock(ThrowCompletion::class, [
                    'getValue' => $this->createConfiguredMock(ObjectValue::class, [
                        'get' => $this->createConfiguredMock(ObjectValue::class, [
                            'hasProperty' => $this->createConfiguredMock(BooleanValue::class, [
                                'getValue' => true
                            ]),
                            'get' => $this->createConfiguredMock(StringValue::class, [
                                'getValue' => 'SyntaxError',
                                '__toString' => 'SyntaxError',
                            ]),

                        ])
                    ])
                ])
            ])
        );

        $config = $this->createConfiguredMock(TestConfig::class, [
            'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                'negative' => $this->createConfiguredMock(FrontmatterNegative::class, [
                    'phase' => FrontmatterNegativePhase::parse,
                    'type' => 'SyntaxError'
                ])
            ])
        ]);

        $actual = $testRunner->run($config);

        $this->assertSame(TestResultState::Success, $actual->state());
    }
}
