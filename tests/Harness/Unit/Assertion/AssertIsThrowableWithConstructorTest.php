<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Assertion;

use Oru\EcmaScript\Harness\Assertion\AssertIsThrowableWithConstructor;
use Oru\EcmaScript\Harness\Assertion\Exception\AssertionFailedException;
use Oru\EcmaScript\Harness\Assertion\Exception\EngineException;
use Oru\EcmaScript\Harness\Contracts\Facade;
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

        $facadeMock = $this->createConfiguredMock(Facade::class, [
            'isThrowCompletion' => false,
        ]);

        $assertion = new AssertIsThrowableWithConstructor(
            $facadeMock,
            $this->createMock(FrontmatterNegative::class)
        );
        $assertion->assert('NOT ThrowCompletion');
    }

    #[Test]
    public function throwsWhenProvidedThrowCompletionDoesNotContainAnObject(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('`ThrowCompletion` does not contain an `ObjectValue`'));

        $facadeMock = $this->createConfiguredMock(Facade::class, [
            'isThrowCompletion' => true,
            'isObject' => false,
        ]);

        $assertion = new AssertIsThrowableWithConstructor(
            $facadeMock,
            $this->createMock(FrontmatterNegative::class)
        );
        $assertion->assert('ThrowCompletion');
    }

    #[Test]
    public function throwsExceptionWhenConstructorCannotGetExtracted(): void
    {
        $this->expectExceptionObject(new EngineException('Could not use `get()` to retrieve `constructor`'));

        $facadeMock = $this->createConfiguredMock(Facade::class, [
            'isThrowCompletion' => true,
            'isObject' => true,
        ]);
        $facadeMock->method('objectGet')->willThrowException(
            $this->createMock(Throwable::class)
        );

        $assertion = new AssertIsThrowableWithConstructor(
            $facadeMock,
            $this->createMock(FrontmatterNegative::class)
        );
        $assertion->assert('ThrowCompletion');
    }

    #[Test]
    public function forNegativeCasesReturnsFailureWhenConstructorIsNotAnObject(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('Constructor value is not an `ObjectValue`'));

        $facadeMock = $this->createConfiguredMock(Facade::class, [
            'isThrowCompletion' => true,
            'objectGet' => 'UndefinedValue'
        ]);
        $facadeMock->method('isObject')->willReturn(true, false);

        $assertion = new AssertIsThrowableWithConstructor(
            $facadeMock,
            $this->createMock(FrontmatterNegative::class)
        );
        $assertion->assert('ThrowCompletion');
    }

    #[Test]
    public function throwsExceptionWhenConstructorPropertyCheckThrows(): void
    {
        $this->expectExceptionObject(new EngineException('Could not use `hasName()` to check existence of `name`'));

        $facadeMock = $this->createConfiguredMock(Facade::class, [
            'isThrowCompletion' => true,
            'isObject' => true,
            'objectGet' => 'ObjectValue'
        ]);
        $facadeMock->method('objectHasProperty')->willThrowException(
            $this->createMock(Throwable::class)
        );

        $assertion = new AssertIsThrowableWithConstructor(
            $facadeMock,
            $this->createMock(FrontmatterNegative::class)
        );
        $assertion->assert('ThrowCompletion');
    }

    #[Test]
    public function throwsWhenConstructorHasNotANameProperty(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('Constructor does not have a name'));

        $facadeMock = $this->createConfiguredMock(Facade::class, [
            'isThrowCompletion' => true,
            'isObject' => true,
            'objectGet' => 'ObjectValue',
            'objectHasProperty' => false
        ]);

        $assertion = new AssertIsThrowableWithConstructor(
            $facadeMock,
            $this->createMock(FrontmatterNegative::class)
        );
        $assertion->assert('ThrowCompletion');
    }

    #[Test]
    public function throwsExceptionWhenConstructorNameCannotGetExtracted(): void
    {
        $this->expectExceptionObject(new EngineException('Could not use `get()` to retrieve `constructor.name`'));

        $facadeMock = $this->createConfiguredMock(Facade::class, [
            'isThrowCompletion' => true,
            'isObject' => true,
            'objectHasProperty' => true
        ]);
        $facadeMock->method('objectGet')->willReturnCallback(
            function (): string {
                static $count = 0;
                return match ($count++) {
                    0 => 'ObjectValue',
                    default => throw $this->createMock(Throwable::class)
                };
            }
        );

        $assertion = new AssertIsThrowableWithConstructor(
            $facadeMock,
            $this->createMock(FrontmatterNegative::class)
        );
        $assertion->assert('ThrowCompletion');
    }

    #[Test]
    public function throwsWhenConstructorNameDoesNotMatch(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('Expected `SyntaxError` but got ``'));

        $facadeMock = $this->createConfiguredMock(Facade::class, [
            'isThrowCompletion' => true,
            'isObject' => true,
            'objectGet' => 'ObjectValue',
            'objectHasProperty' => true
        ]);

        $assertion = new AssertIsThrowableWithConstructor(
            $facadeMock,
            $this->createConfiguredMock(FrontmatterNegative::class, [
                'phase' => FrontmatterNegativePhase::parse,
                'type' => 'SyntaxError'
            ])
        );
        $assertion->assert('ThrowCompletion');
    }

    #[Test]
    public function throwsWhenConstructorNameStringConversionFails(): void
    {
        $this->expectExceptionObject(new EngineException('Could not convert `name` to string'));

        $facadeMock = $this->createConfiguredMock(Facade::class, [
            'isThrowCompletion' => true,
            'isObject' => true,
            'objectGet' => 'ObjectValue',
            'objectHasProperty' => true
        ]);
        $facadeMock->method('toString')->willThrowException(
            $this->createMock(Throwable::class)
        );

        $assertion = new AssertIsThrowableWithConstructor(
            $facadeMock,
            $this->createMock(FrontmatterNegative::class)
        );
        $assertion->assert('ThrowCompletion');
    }

    #[Test]
    public function returnsNullWhenConstructorNameMatches(): void
    {
        $facadeMock = $this->createConfiguredMock(Facade::class, [
            'isThrowCompletion' => true,
            'isObject' => true,
            'objectGet' => 'ObjectValue',
            'objectHasProperty' => true,
            'toString' => 'SyntaxError'
        ]);

        $assertion = new AssertIsThrowableWithConstructor(
            $facadeMock,
            $this->createConfiguredMock(FrontmatterNegative::class, [
                'phase' => FrontmatterNegativePhase::parse,
                'type' => 'SyntaxError'
            ])
        );
        $actual = $assertion->assert('ThrowCompletion');

        $this->assertNull($actual);
    }
}
