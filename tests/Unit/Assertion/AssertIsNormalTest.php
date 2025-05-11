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

use Oru\EcmaScript\Core\Contracts\Values\NumberValue;
use Oru\EcmaScript\Core\Contracts\Values\ObjectValue;
use Oru\EcmaScript\Core\Contracts\Values\StringValue;
use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
use Oru\EcmaScript\Core\Contracts\Values\ValueFactory;
use Oru\Harness\Assertion\AssertIsNormal;
use Oru\Harness\Assertion\Exception\AssertionFailedException;
use Oru\Harness\Assertion\Exception\EngineException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssertIsNormal::class)]
final class AssertIsNormalTest extends TestCase
{
    private function createAssertIsNormal(): AssertIsNormal
    {
        $valueFactory = $this->createStub(ValueFactory::class);
        $valueFactory->method('createString')->willReturnCallback(
            fn(string $string): StringValue => $this->createConfiguredStub(StringValue::class, [
                'getValue' => $string,
                '__toString' => $string,
            ])
        );

        return new AssertIsNormal($valueFactory);
    }

    #[Test]
    public function throwsWhenProvidedValueIsNeitherThrowCompletionNorNormalCompletion(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('Expected `NormalCompletion`'));

        $assertion = $this->createAssertIsNormal();

        $assertion->assert('AbruptCompletion');
    }

    #[Test]
    public function throwsWhenProvidedValueIsAThrowCompletionWithNonObjectInside(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('123.1'));

        $assertion = $this->createAssertIsNormal();
        $value = $this->createStub(ThrowCompletion::class);
        $value->method('getValue')->willReturn(
            $this->createConfiguredStub(NumberValue::class, [
                'getValue' => 123.1,
            ]),
        );

        $assertion->assert($value);
    }

    #[Test]
    public function throwsWhenExceptionMessageIsUndefined(): void
    {
        $this->expectExceptionObject(new EngineException('Object property `message` was empty'));

        $assertion = $this->createAssertIsNormal();
        $value = $this->createStub(ThrowCompletion::class);
        $value->method('getValue')->willReturn(
            $this->createConfiguredStub(ObjectValue::class, [
                'get' => $this->createConfiguredStub(StringValue::class, [
                    'getValue' => '',
                    '__toString' => '',
                ])
            ]),
        );

        $assertion->assert($value);
    }

    #[Test]
    public function throwsWhenExceptionMessageStringConversionFails(): void
    {
        $this->expectExceptionObject(new EngineException('Could not convert object property `message` to string'));

        $assertion = $this->createAssertIsNormal();
        $object = $this->createStub(ObjectValue::class);
        $object->method('get')->willThrowException(
            $this->createStub(ThrowCompletion::class)
        );

        $value = $this->createStub(ThrowCompletion::class);
        $value->method('getValue')->willReturn($object);

        $assertion->assert($value);
    }

    #[Test]
    public function returnsWhenProvidedValueIsNotAnAbruptCompletion(): void
    {
        $assertion = $this->createAssertIsNormal();
        $value = $this->createStub(StringValue::class);

        $actual = $assertion->assert($value);

        $this->assertNull($actual);
    }

    #[Test]
    public function throwsWithTheContainedMessageInProvidedThrowCompletion(): void
    {
        $expectedMessage = 12345678.9;
        $this->expectExceptionObject(new AssertionFailedException((string) $expectedMessage));

        $assertion = $this->createAssertIsNormal();
        $value = $this->createStub(ThrowCompletion::class);
        $value->method('getValue')->willReturn(
            $this->createConfiguredStub(ObjectValue::class, [
                'get' => $this->createConfiguredStub(NumberValue::class, [
                    'getValue' => $expectedMessage,
                ])
            ]),
        );

        $assertion->assert($value);
    }
}
