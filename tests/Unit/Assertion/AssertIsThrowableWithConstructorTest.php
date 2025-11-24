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

namespace Tests\Unit\Assertion;

use Oru\EcmaScript\Core\Contracts\Values\BooleanValue;
use Oru\EcmaScript\Core\Contracts\Values\LanguageValue;
use Oru\EcmaScript\Core\Contracts\Values\NumberValue;
use Oru\EcmaScript\Core\Contracts\Values\ObjectValue;
use Oru\EcmaScript\Core\Contracts\Values\StringValue;
use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
use Oru\EcmaScript\Core\Contracts\Values\UnusedValue;
use Oru\Harness\Assertion\AssertIsThrowableWithConstructor;
use Oru\Harness\Assertion\Exception\AssertionFailedException;
use Oru\Harness\Assertion\Exception\EngineException;
use Oru\Harness\Contracts\FrontmatterNegative;
use Oru\Harness\Contracts\FrontmatterNegativePhase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssertIsThrowableWithConstructor::class)]
final class AssertIsThrowableWithConstructorTest extends TestCase
{
    private function createAssertIsThrowableWithConstructor(?FrontmatterNegative $frontmatterNegative = null): AssertIsThrowableWithConstructor
    {
        $frontmatterNegative ??= $this->createStub(FrontmatterNegative::class);

        return new AssertIsThrowableWithConstructor($frontmatterNegative);
    }

    #[Test]
    public function throwsWhenProvidedValueIsNotAThrowCompletion(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('Expected `ThrowCompletion`'));

        $assertion = $this->createAssertIsThrowableWithConstructor();

        $assertion->assert('NOT ThrowCompletion');
    }

    #[Test]
    public function throwsWhenProvidedThrowCompletionDoesNotContainAnObject(): void
    {
        $this->expectExceptionObject(new AssertionFailedException("`ThrowCompletion` does not contain an `ObjectValue`, got '12345678.9'"));

        $assertion = $this->createAssertIsThrowableWithConstructor();
        $value = $this->createStub(ThrowCompletion::class);
        $value->method('getValue')->willReturn(
            $this->createConfiguredStub(NumberValue::class, [
                'getValue' => 12345678.9,
            ])
        );

        $assertion->assert($value);
    }

    #[Test]
    public function throwsExceptionWhenConstructorCannotGetExtracted(): void
    {
        $this->expectExceptionObject(new EngineException('Could not use `get()` to retrieve `constructor`'));

        $assertion = $this->createAssertIsThrowableWithConstructor();
        $object = $this->createStub(ObjectValue::class);
        $object->method('get')->willThrowException(
            $this->createStub(ThrowCompletion::class)
        );
        $value = $this->createConfiguredStub(ThrowCompletion::class, [
            'getValue' => $object
        ]);

        $assertion->assert($value);
    }

    #[Test]
    public function forNegativeCasesReturnsFailureWhenConstructorIsNotAnObject(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('Constructor value is not an `ObjectValue`'));

        $assertion = $this->createAssertIsThrowableWithConstructor();
        $value = $this->createStub(ThrowCompletion::class);
        $value->method('getValue')->willReturn(
            $this->createConfiguredStub(ObjectValue::class, [
                'get' => $this->createStub(StringValue::class),
            ])
        );

        $assertion->assert($value);
    }

    #[Test]
    public function throwsExceptionWhenConstructorPropertyCheckThrows(): void
    {
        $this->expectExceptionObject(new EngineException('Could not use `hasProperty()` to check existence of `name`'));

        $assertion = $this->createAssertIsThrowableWithConstructor();
        $object = $this->createStub(ObjectValue::class);
        $object->method('hasProperty')->willThrowException(
            $this->createStub(ThrowCompletion::class)
        );
        $value = $this->createStub(ThrowCompletion::class);
        $value->method('getValue')->willReturn(
            $this->createConfiguredStub(ObjectValue::class, [
                'get' => $object,
            ])
        );

        $assertion->assert($value);
    }

    #[Test]
    public function throwsWhenConstructorHasNotANameProperty(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('Constructor does not have a name'));

        $assertion = $this->createAssertIsThrowableWithConstructor();
        $value = $this->createStub(ThrowCompletion::class);
        $value->method('getValue')->willReturn(
            $this->createConfiguredStub(ObjectValue::class, [
                'get' => $this->createConfiguredStub(ObjectValue::class, [
                    'hasProperty' => $this->createConfiguredStub(BooleanValue::class, [
                        'getValue' => false,
                    ]),
                ])
            ])
        );

        $assertion->assert($value);
    }

    #[Test]
    public function throwsExceptionWhenConstructorNameCannotGetExtracted(): void
    {
        $this->expectExceptionObject(new EngineException('Could not use `get()` to retrieve `constructor.name`'));

        $assertion = $this->createAssertIsThrowableWithConstructor();
        $object = $this->createConfiguredStub(ObjectValue::class, [
            'hasProperty' => $this->createConfiguredStub(BooleanValue::class, [
                'getValue' => true,
            ]),
        ]);
        $object->method('get')->willThrowException(
            $this->createStub(ThrowCompletion::class)
        );
        $value = $this->createConfiguredStub(ThrowCompletion::class, [
            'getValue' => $this->createConfiguredStub(ObjectValue::class, [
                'get' => $object,
            ]),
        ]);

        $assertion->assert($value);
    }

    #[Test]
    public function throwsWhenConstructorNameDoesNotMatch(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('Expected `SyntaxError` but got ``'));

        $assertion = $this->createAssertIsThrowableWithConstructor(
            frontmatterNegative: $this->createConfiguredMock(FrontmatterNegative::class, [
                'phase' => FrontmatterNegativePhase::parse,
                'type' => 'SyntaxError'
            ]),
        );
        $value = $this->createStub(ThrowCompletion::class);
        $value->method('getValue')->willReturn(
            $this->createConfiguredStub(ObjectValue::class, [
                'get' => $this->createConfiguredStub(ObjectValue::class, [
                    'hasProperty' => $this->createConfiguredStub(BooleanValue::class, [
                        'getValue' => true,
                    ]),
                    'get' => $this->createStub(StringValue::class),
                ]),
            ]),
        );

        $assertion->assert($value);
    }

    #[Test]
    public function throwsWhenConstructorNameStringConversionFails(): void
    {
        $this->expectExceptionObject(new EngineException('Could not convert `name` to string'));

        $assertion = $this->createAssertIsThrowableWithConstructor();
        $name = $this->createConfiguredStub(LanguageValue::class, [
            'getValue' => $this->createStub(UnusedValue::class),
        ]);
        $value = $this->createStub(ThrowCompletion::class);
        $value->method('getValue')->willReturn(
            $this->createConfiguredStub(ObjectValue::class, [
                'get' => $this->createConfiguredStub(ObjectValue::class, [
                    'hasProperty' => $this->createConfiguredStub(BooleanValue::class, [
                        'getValue' => true,
                    ]),
                    'get' => $name,
                ]),
            ]),
        );

        $assertion->assert($value);
    }

    #[Test]
    public function returnsNullWhenConstructorNameMatches(): void
    {
        $assertion = $this->createAssertIsThrowableWithConstructor(
            frontmatterNegative: $this->createConfiguredMock(FrontmatterNegative::class, [
                'phase' => FrontmatterNegativePhase::parse,
                'type' => 'SyntaxError'
            ]),
        );
        $value = $this->createStub(ThrowCompletion::class);
        $value->method('getValue')->willReturn(
            $this->createConfiguredStub(ObjectValue::class, [
                'get' => $this->createConfiguredStub(ObjectValue::class, [
                    'hasProperty' => $this->createConfiguredStub(BooleanValue::class, [
                        'getValue' => true,
                    ]),
                    'get' => $this->createConfiguredStub(StringValue::class, [
                        'getValue' => 'SyntaxError',
                        '__toString' => 'SyntaxError',
                    ]),
                ]),
            ]),
        );

        $actual = $assertion->assert($value);

        $this->assertNull($actual);
    }
}
