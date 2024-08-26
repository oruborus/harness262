<?php

/**
 * Copyright (c) 2024, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use Generator;
use Oru\Harness\Assertion\Exception\AssertionFailedException;
use Oru\Harness\Helpers\SerializableTransformer;
use Oru\Harness\TestResult\GenericTestResultFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Utility\Engine\TestThrowCompletion;
use Throwable;

use function serialize;

#[CoversClass(SerializableTransformer::class)]
final class SerializableTransformerTest extends TestCase
{
    private function assertIsSerializable(mixed $actual): void
    {
        $this->assertTrue(
            $this->isSerializable($actual),
            'Could not assert that value is serializable'
        );
    }

    private function assertIsNotSerializable(mixed $actual): void
    {
        $this->assertFalse(
            $this->isSerializable($actual),
            'Could not assert that value is not serializable'
        );
    }

    private function isSerializable(mixed $actual): bool
    {
        try {
            serialize($actual);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    #[Test]
    #[DataProvider('provideScalar')]
    public function worksWithScalars(mixed $scalar): void
    {
        $transformer = new SerializableTransformer();

        $actual = $transformer->transform($scalar);

        $this->assertIsSerializable($actual);
        $this->assertSame($scalar, $actual);
    }

    public static function provideScalar(): Generator
    {
        yield 'int' => [5];
        yield 'float' => [3.1415];
        yield 'string' => ['scalar'];
        yield 'true' => [true];
        yield 'false' => [false];
    }

    #[Test]
    public function worksWithNull(): void
    {
        $transformer = new SerializableTransformer();

        $actual = $transformer->transform(null);

        $this->assertIsSerializable($actual);
        $this->assertNull($actual);
    }

    #[Test]
    #[DataProvider('provideUnserializableObject')]
    public function worksWithObjectWithUnserializableMemberInObjectTree(object $object): void
    {
        $transformer = new SerializableTransformer();

        $actual = $transformer->transform($object);

        $this->assertIsNotSerializable($object);
        $this->assertIsSerializable($actual);
    }

    public static function provideUnserializableObject(): Generator
    {
        // yield 'first layer anonymous' => [new A(new class {})];
        // yield 'second layer anonymous' => [new A(new A(new class {}))];
        // yield 'first layer reflection' => [new A(new ReflectionClass(new class {}))];
        // yield 'second layer reflection' => [new A(new A(new ReflectionClass(new class {})))];
        // yield 'TestThrowCompletion' => [new TestThrowCompletion(true)];

        ini_set('zend.exception_ignore_args', 1);
        yield 'GenericTestResult' => [
            (new GenericTestResultFactory())->makeFailed('', [], 0, new AssertionFailedException())
        ];
        ini_set('zend.exception_ignore_args', 0);
    }
}

final readonly class A
{
    public function __construct(
        private mixed $a,
    ) {}
}
