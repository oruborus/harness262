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

use Closure;
use Exception;
use Fiber;
use Generator;
use Oru\Harness\Assertion\Exception\AssertionFailedException;
use Oru\Harness\Helpers\SerializationSanitizer;
use Oru\Harness\TestResult\GenericTestResultFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Tests\Utility\Engine\TestThrowCompletion;
use Throwable;

use function fclose;
use function fopen;
use function serialize;

#[CoversClass(SerializationSanitizer::class)]
final class SerializationSanitizerTest extends TestCase
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
    #[DataProvider('provideSerializableValue')]
    public function doesNotChangeSerializableValues(mixed $expected): void
    {
        $transformer = new SerializationSanitizer();

        $actual = $transformer->sanitize($expected);

        $this->assertIsSerializable($actual);
        $this->assertEquals($expected, $actual);
    }

    public static function provideSerializableValue(): Generator
    {
        yield 'boolean' => [true];
        yield 'integer' => [123];
        yield 'float' => [123.456];
        yield 'string' => ['STRING'];
        $resource = fopen('php://input', 'r');
        yield 'resource' => [$resource];
        fclose($resource);
        yield 'resource (closed)' => [$resource];
        yield 'null' => [null];

        yield 'enum' => [TestEnum::TEST];

        yield 'defined class' => [new A(null)];
        yield 'array' => [[1, 2, 3, [4, 5, 6]]];

        $a = new A(null);
        $b = new A($a);
        $a->a = $b;
        yield 'circular references' => [$a];
    }

    #[Test]
    #[DataProvider('provideUnserializableTree')]
    public function worksWithUnserializableElementInTree(object|array $input): void
    {
        $transformer = new SerializationSanitizer();

        $actual = $transformer->sanitize($input);

        $this->assertIsNotSerializable($input);
        $this->assertIsSerializable($actual);
    }

    public static function provideUnserializableTree(): Generator
    {
        yield 'Reflector' => [new ReflectionClass(new Exception())];
        yield 'anonymous class' => [new class {}];
        yield 'Closure' => [static fn(): null => null];
        yield 'Fiber' => [new Fiber(static fn(): null => null)];

        yield 'first object layer anonymous' => [new A(new class {})];
        yield 'second object layer anonymous' => [new A(new A(new class {}))];
        yield 'first object layer reflection' => [new A(new ReflectionClass(new class {}))];
        yield 'second object layer reflection' => [new A(new A(new ReflectionClass(new class {})))];
        yield 'first array layer anonymous' => [[1, 2, 3, new class {}]];
        yield 'second array layer anonymous' => [[1, 2, 3, [1, 2, 3, new class {}]]];
        yield 'first array layer reflection' => [[1, 2, 3, new ReflectionClass(new class {})]];
        yield 'second array layer reflection' => [[1, 2, 3, [1, 2, 3, new ReflectionClass(new class {})]]];

        $a = new A(null, fn() => null);
        $b = new A($a);
        $a->a = $b;
        yield 'circular references' => [[1, 2, $a, [1, 2, $a, new ReflectionClass(new class {})]]];

        yield 'TestThrowCompletion' => [new TestThrowCompletion(true)];

        yield 'GenericTestResult' => [
            (new GenericTestResultFactory())->makeFailed('', [], 0, new AssertionFailedException())
        ];

        $x = new RuntimeException();
        (new ReflectionClass(Exception::class))
            ->getProperty('trace')
            ->setValue($x, [['args' => [static fn(): null => null]]]);
        yield 'captured function call argument in trace' => [$x];

        yield 'private property of parent class' => [new B('value')];

        yield 'uninitialized property' => [new D()];
    }
}

class A
{
    public function __construct(
        public mixed $a,
        private mixed $b = null,
    ) {}
}

final class B extends A
{
    public function __construct(
        public mixed $a,
        private mixed $b = null,
    ) {
        parent::__construct(null, static fn(): null => null);
    }
}

final class C extends A
{
    public A $c;
}

final class D
{
    private A $a;

    private Closure $b;

    public function __construct()
    {
        $this->b  = static fn(): null => null;
    }
}

enum TestEnum
{
    case TEST;
}
