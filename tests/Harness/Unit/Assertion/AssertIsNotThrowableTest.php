<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Assertion;

use Oru\EcmaScript\Core\Contracts\Agent;
use Oru\EcmaScript\Core\Contracts\Values\AbruptCompletion;
use Oru\EcmaScript\Core\Contracts\Values\NumberValue;
use Oru\EcmaScript\Core\Contracts\Values\ObjectValue;
use Oru\EcmaScript\Core\Contracts\Values\PropertyDescriptor;
use Oru\EcmaScript\Core\Contracts\Values\StringValue;
use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
use Oru\EcmaScript\Core\Contracts\Values\UndefinedValue;
use Oru\EcmaScript\Harness\Assertion\AssertIsNotThrowable;
use Oru\EcmaScript\Harness\Assertion\Exception\AssertionFailedException;
use Oru\EcmaScript\Harness\Assertion\Exception\EngineException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversClass(AssertIsNotThrowable::class)]
final class AssertIsNotThrowableTest extends TestCase
{
    #[Test]
    public function throwsWhenProvidedValueIsNeitherThrowCompletionNorNormalCompletion(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('Expected `NormalCompletion`'));

        $assertion = new AssertIsNotThrowable($this->createMock(Agent::class));
        $assertion->assert($this->createMock(AbruptCompletion::class));
    }

    #[Test]
    public function throwsWhenProvidedValueIsAThrowCompletionWithNonObjectInside(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('123'));

        $assertion = new AssertIsNotThrowable($this->createMock(Agent::class));
        $assertion->assert($this->createConfiguredMock(ThrowCompletion::class, [
            'getValue' => $this->createConfiguredMock(NumberValue::class, [
                'getValue' => 123.0
            ])
        ]));
    }

    #[Test]
    public function throwsWhenExceptionMessageCannotGetExtracted(): void
    {
        $this->expectExceptionObject(new EngineException('Could not use `Object.[[GetOwnProperty]]()` to retrieve `message`'));

        $exceptionObject = $this->createMock(ObjectValue::class);
        $exceptionObject->method('getOwnProperty')->willThrowException(
            $this->createMockForIntersectionOfInterfaces([AbruptCompletion::class, Throwable::class])
        );

        $assertion = new AssertIsNotThrowable($this->createMock(Agent::class));
        $assertion->assert(
            $this->createConfiguredMock(ThrowCompletion::class, [
                'getValue' => $exceptionObject
            ])
        );
    }

    #[Test]
    public function throwsWhenExceptionMessageIsUndefined(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('EngineError without message :('));

        $assertion = new AssertIsNotThrowable($this->createMock(Agent::class));
        $assertion->assert(
            $this->createConfiguredMock(ThrowCompletion::class, [
                'getValue' =>
                $this->createConfiguredMock(ObjectValue::class, [
                    'getOwnProperty' => $this->createMock(UndefinedValue::class)
                ])
            ])
        );
    }

    #[Test]
    public function throwsWhenExceptionMessageStringConversionFails(): void
    {
        $this->expectExceptionObject(new EngineException('Could not convert `message` to string'));

        $propertyDescriptorMock = $this->createMock(PropertyDescriptor::class);
        $propertyDescriptorMock->method('getValue')->willThrowException(
            $this->createMockForIntersectionOfInterfaces([AbruptCompletion::class, Throwable::class])
        );
        $assertion = new AssertIsNotThrowable($this->createMock(Agent::class));
        $assertion->assert(
            $this->createConfiguredMock(ThrowCompletion::class, [
                'getValue' =>
                $this->createConfiguredMock(ObjectValue::class, [
                    'getOwnProperty' => $propertyDescriptorMock
                ])
            ])
        );
    }

    #[Test]
    public function throwsExceptionWithMessage(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('The correct exception message'));

        $assertion = new AssertIsNotThrowable($this->createMock(Agent::class));
        $assertion->assert(
            $this->createConfiguredMock(ThrowCompletion::class, [
                'getValue' =>
                $this->createConfiguredMock(ObjectValue::class, [
                    'getOwnProperty' => $this->createConfiguredMock(PropertyDescriptor::class, [
                        'getValue' => $this->createConfiguredMock(StringValue::class, [
                            'getValue' => 'The correct exception message',
                            '__toString' => 'The correct exception message'
                        ])
                    ])
                ])
            ])
        );
    }

    #[Test]
    public function returnsWhenProvidedValueIsNotAnAbruptCompletion(): void
    {
        $assertion = new AssertIsNotThrowable($this->createMock(Agent::class));

        $actual = $assertion->assert(
            $this->createMock(UndefinedValue::class)
        );

        $this->assertNull($actual);
    }
}
