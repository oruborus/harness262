<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Assertion;

use Oru\EcmaScript\Core\Contracts\Agent;
use Oru\EcmaScript\Core\Contracts\Values\AbruptCompletion;
use Oru\EcmaScript\Core\Contracts\Values\BooleanValue;
use Oru\EcmaScript\Core\Contracts\Values\ObjectValue;
use Oru\EcmaScript\Core\Contracts\Values\StringValue;
use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
use Oru\EcmaScript\Core\Contracts\Values\UndefinedValue;
use Oru\EcmaScript\Harness\Assertion\AssertIsThrowableWithConstructor;
use Oru\EcmaScript\Harness\Assertion\Exception\AssertionFailedException;
use Oru\EcmaScript\Harness\Assertion\Exception\EngineException;
use Oru\EcmaScript\Harness\Contracts\FrontmatterNegative;
use Oru\EcmaScript\Harness\Contracts\FrontmatterNegativePhase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversClass(AssertIsThrowableWithConstructor::class)]
final class AssertIsThrowableWithConstructorTest extends TestCase
{
    #[Test]
    public function throwsWhenProvidedValueIsNotAThrowCompletion(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('Expected `ThrowCompletion`'));

        $assertion = new AssertIsThrowableWithConstructor(
            $this->createMock(Agent::class),
            $this->createConfiguredMock(FrontmatterNegative::class, [
                'phase' => FrontmatterNegativePhase::parse,
                'type' => 'SyntaxError'
            ])
        );

        $assertion->assert($this->createMock(UndefinedValue::class));
    }

    #[Test]
    public function throwsWhenProvidedThrowCompletionDoesNotContainAnObject(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('`ThrowCompletion` does not contain an `ObjectValue`'));

        $assertion = new AssertIsThrowableWithConstructor(
            $this->createMock(Agent::class),
            $this->createConfiguredMock(FrontmatterNegative::class, [
                'phase' => FrontmatterNegativePhase::parse,
                'type' => 'SyntaxError'
            ])
        );

        $assertion->assert(
            $this->createConfiguredMock(ThrowCompletion::class, [
                'getValue' => $this->createMock(UndefinedValue::class)
            ])
        );
    }

    #[Test]
    public function throwsExceptionWhenConstructorCannotGetExtracted(): void
    {
        $this->expectExceptionObject(new EngineException('Could not use `get()` to retrieve `constructor`'));

        $exceptionObject = $this->createMock(ObjectValue::class);
        $exceptionObject->method('get')->willThrowException(
            $this->createMockForIntersectionOfInterfaces([AbruptCompletion::class, Throwable::class])
        );
        $assertion = new AssertIsThrowableWithConstructor(
            $this->createMock(Agent::class),
            $this->createConfiguredMock(FrontmatterNegative::class, [
                'phase' => FrontmatterNegativePhase::parse,
                'type' => 'SyntaxError'
            ])
        );

        $assertion->assert(
            $this->createConfiguredMock(ThrowCompletion::class, [
                'getValue' => $exceptionObject
            ])
        );
    }

    #[Test]
    public function forNegativeCasesReturnsFailureWhenConstructorIsNotAnObject(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('Constructor value is not an `ObjectValue`'));

        $assertion = new AssertIsThrowableWithConstructor(
            $this->createMock(Agent::class),
            $this->createConfiguredMock(FrontmatterNegative::class, [
                'phase' => FrontmatterNegativePhase::parse,
                'type' => 'SyntaxError'
            ])
        );

        $assertion->assert(
            $this->createConfiguredMock(ThrowCompletion::class, [
                'getValue' => $this->createConfiguredMock(ObjectValue::class, [
                    'get' => $this->createMock(UndefinedValue::class)
                ])
            ])
        );
    }

    #[Test]
    public function throwsExceptionWhenConstructorPropertyCheckThrows(): void
    {
        $this->expectExceptionObject(new EngineException('Could not use `hasName()` to to check existence of `name`'));

        $constructorObject = $this->createMock(ObjectValue::class);
        $constructorObject->method('hasProperty')->willThrowException(
            $this->createMockForIntersectionOfInterfaces([AbruptCompletion::class, Throwable::class])
        );
        $assertion = new AssertIsThrowableWithConstructor(
            $this->createMock(Agent::class),
            $this->createConfiguredMock(FrontmatterNegative::class, [
                'phase' => FrontmatterNegativePhase::parse,
                'type' => 'SyntaxError'
            ])
        );

        $assertion->assert(
            $this->createConfiguredMock(ThrowCompletion::class, [
                'getValue' => $this->createConfiguredMock(ObjectValue::class, [
                    'get' => $constructorObject
                ])
            ])
        );
    }

    #[Test]
    public function throwsWhenConstructorHasNotANameProperty(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('Constructor does not have a name'));

        $assertion = new AssertIsThrowableWithConstructor(
            $this->createMock(Agent::class),
            $this->createConfiguredMock(FrontmatterNegative::class, [
                'phase' => FrontmatterNegativePhase::parse,
                'type' => 'SyntaxError'
            ])
        );

        $assertion->assert(
            $this->createConfiguredMock(ThrowCompletion::class, [
                'getValue' => $this->createConfiguredMock(ObjectValue::class, [
                    'get' => $this->createConfiguredMock(ObjectValue::class, [
                        'hasProperty' => $this->createConfiguredMock(BooleanValue::class, [
                            'getValue' => false
                        ])
                    ])
                ])
            ])
        );
    }

    #[Test]
    public function throwsExceptionWhenConstructorNameCannotGetExtracted(): void
    {
        $this->expectExceptionObject(new EngineException('Could not use `get()` to retrieve `constructor.name`'));

        $constructorObject = $this->createMock(ObjectValue::class);
        $constructorObject->method('hasProperty')->willReturn(
            $this->createConfiguredMock(BooleanValue::class, [
                'getValue' => true
            ])
        );
        $constructorObject->method('get')->willThrowException(
            $this->createMockForIntersectionOfInterfaces([AbruptCompletion::class, Throwable::class])
        );
        $assertion = new AssertIsThrowableWithConstructor(
            $this->createMock(Agent::class),
            $this->createConfiguredMock(FrontmatterNegative::class, [
                'phase' => FrontmatterNegativePhase::parse,
                'type' => 'SyntaxError'
            ])
        );

        $assertion->assert(
            $this->createConfiguredMock(ThrowCompletion::class, [
                'getValue' => $this->createConfiguredMock(ObjectValue::class, [
                    'get' => $constructorObject
                ])
            ])
        );
    }

    #[Test]
    public function throwsWhenConstructorNameDoesNotMatch(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('Expected `SyntaxError` but got `WRONG`'));

        $assertion = new AssertIsThrowableWithConstructor(
            $this->createMock(Agent::class),
            $this->createConfiguredMock(FrontmatterNegative::class, [
                'phase' => FrontmatterNegativePhase::parse,
                'type' => 'SyntaxError'
            ])
        );

        $assertion->assert(
            $this->createConfiguredMock(ThrowCompletion::class, [
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
        );
    }

    #[Test]
    public function throwsWhenConstructorNameStringConversionFails(): void
    {
        $this->expectExceptionObject(new EngineException('Could not convert `name` to string'));

        $assertion = new AssertIsThrowableWithConstructor(
            $this->createMock(Agent::class),
            $this->createConfiguredMock(FrontmatterNegative::class, [
                'phase' => FrontmatterNegativePhase::parse,
                'type' => 'SyntaxError'
            ])
        );
        $stringMock = $this->createMock(StringValue::class);
        $stringMock->method('getValue')->willThrowException(
            $this->createMockForIntersectionOfInterfaces([AbruptCompletion::class, Throwable::class])
        );
        $stringMock->method('__toString')->willThrowException(
            $this->createMockForIntersectionOfInterfaces([AbruptCompletion::class, Throwable::class])
        );

        $assertion->assert(
            $this->createConfiguredMock(ThrowCompletion::class, [
                'getValue' => $this->createConfiguredMock(ObjectValue::class, [
                    'get' => $this->createConfiguredMock(ObjectValue::class, [
                        'hasProperty' => $this->createConfiguredMock(BooleanValue::class, [
                            'getValue' => true
                        ]),
                        'get' => $stringMock,

                    ])
                ])
            ])
        );
    }

    #[Test]
    public function returnsNullWhenConstructorNameMatches(): void
    {
        $assertion = new AssertIsThrowableWithConstructor(
            $this->createMock(Agent::class),
            $this->createConfiguredMock(FrontmatterNegative::class, [
                'phase' => FrontmatterNegativePhase::parse,
                'type' => 'SyntaxError'
            ])
        );

        $actual = $assertion->assert(
            $this->createConfiguredMock(ThrowCompletion::class, [
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
        );

        $this->assertNull($actual);
    }
}
