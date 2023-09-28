<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Assertion;

use Oru\EcmaScript\Harness\Assertion\AssertIsNormal;
use Oru\EcmaScript\Harness\Assertion\Exception\AssertionFailedException;
use Oru\EcmaScript\Harness\Assertion\Exception\EngineException;
use Oru\EcmaScript\Harness\Contracts\Facade;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversClass(AssertIsNormal::class)]
final class AssertIsNormalTest extends TestCase
{
    #[Test]
    public function throwsWhenProvidedValueIsNeitherThrowCompletionNorNormalCompletion(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('Expected `NormalCompletion`'));

        $facadeMock = $this->createConfiguredMock(Facade::class, [
            'isNormalCompletion' => false,
            'isThrowCompletion' => false
        ]);

        $assertion = new AssertIsNormal($facadeMock);
        $assertion->assert('AbruptCompletion');
    }

    #[Test]
    public function throwsWhenProvidedValueIsAThrowCompletionWithNonObjectInside(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('123.1'));

        $facadeMock = $this->createConfiguredMock(Facade::class, [
            'isNormalCompletion' => false,
            'isThrowCompletion' => true,
            'completionGetValue' => 123.1
        ]);

        $assertion = new AssertIsNormal($facadeMock);
        $assertion->assert('ThrowCompletion');
    }

    #[Test]
    public function throwsWhenExceptionMessageIsUndefined(): void
    {
        $this->expectExceptionObject(new EngineException('Object property `message` was empty'));

        $facadeMock = $this->createConfiguredMock(Facade::class, [
            'isThrowCompletion' => true,
            'isNormalCompletion' => false,
            'isObject' => true,
            'completionGetValue' => 'ObjectValue',
            'objectGetAsString' => null
        ]);

        $assertion = new AssertIsNormal($facadeMock);
        $assertion->assert('ThrowCompletion');
    }

    #[Test]
    public function throwsWhenExceptionMessageStringConversionFails(): void
    {
        $this->expectExceptionObject(new EngineException('Could not convert object property `message` to string'));

        $facadeMock = $this->createConfiguredMock(Facade::class, [
            'isNormalCompletion' => false,
            'isThrowCompletion' => true,
            'isObject' => true,
            'completionGetValue' => 'ObjectValue'
        ]);
        $facadeMock->method('objectGetAsString')->willThrowException(
            $this->createMock(Throwable::class)
        );

        $assertion = new AssertIsNormal($facadeMock);
        $assertion->assert('ThrowCompletion');
    }

    #[Test]
    public function returnsWhenProvidedValueIsNotAnAbruptCompletion(): void
    {
        $facadeMock = $this->createConfiguredMock(Facade::class, [
            'isNormalCompletion' => true
        ]);

        $assertion = new AssertIsNormal($facadeMock);
        $actual = $assertion->assert('UndefinedValue');

        $this->assertNull($actual);
    }

    #[Test]
    public function throwsWithTheContainedMessageInProvidedThrowCompletion(): void
    {
        $expectedMessage = 'Error message';
        $this->expectExceptionObject(new AssertionFailedException($expectedMessage));

        $facadeMock = $this->createConfiguredMock(Facade::class, [
            'isNormalCompletion' => false,
            'isThrowCompletion' => true,
            'isObject' => true,
            'objectGetAsString' => $expectedMessage
        ]);

        $assertion = new AssertIsNormal($facadeMock);
        $assertion->assert('ThrowCompletion');
    }
}
