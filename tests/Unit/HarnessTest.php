<?php

/**
 * Copyright (c) 2023-2024, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Tests\Unit;

use Oru\Harness\Contracts\ArgumentsParser;
use Oru\Harness\Contracts\EngineFactory;
use Oru\Harness\Harness;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use const PHP_EOL;

#[CoversClass(Harness::class)]
final class HarnessTest extends TestCase
{
    public const TEMPLATE_PATH = __DIR__ . '/../../src/Template/ExecuteTest.php';

    #[Test]
    public function informsTheUserThatProvidedRegularExpressionPatternIsMalFormed(): void
    {
        $this->expectOutputString(
            PHP_EOL . 'EcmaScript Test Harness' . PHP_EOL .
                PHP_EOL . 'The provided regular expression pattern is malformed.' .
                PHP_EOL . 'The following warning was issued:' .
                PHP_EOL . '"Compilation failed: missing closing parenthesis at offset 1"' . PHP_EOL
        );
        $argumentsParserStub = $this->createConfiguredStub(ArgumentsParser::class, ['rest' => ['./tests/Unit/Fixtures/TestCase/basic.js']]);
        $argumentsParserStub->method('getOption')->willReturnCallback(fn(string $option): string => $option === 'include' ? '(' : '');
        $argumentsParserStub->method('hasOption')->willReturnCallback(fn(string $option): bool => $option === 'include');
        $harness = new Harness(
            $this->createStub(EngineFactory::class),
            $argumentsParserStub,
        );

        $actual = $harness->run();

        $this->assertSame(1, $actual);
    }

    #[Test]
    public function informsTheUserThatProvidedPathIsInvalid(): void
    {
        $expected = '###this/path/does/not/exist###';
        $this->expectOutputString(
            PHP_EOL . 'EcmaScript Test Harness' . PHP_EOL .
                PHP_EOL . "Provided path `{$expected}` does not exist" . PHP_EOL
        );
        $harness = new Harness(
            $this->createStub(EngineFactory::class),
            $this->createConfiguredStub(ArgumentsParser::class, ['rest' => [$expected]]),
        );

        $actual = $harness->run();

        $this->assertSame(1, $actual);
    }

    #[Test]
    public function informsTheUserThatNoPathsWhereProvided(): void
    {
        $this->expectOutputString(
            PHP_EOL . 'EcmaScript Test Harness' . PHP_EOL .
                PHP_EOL . 'No test path specified. Aborting.' . PHP_EOL
        );
        $harness = new Harness(
            $this->createStub(EngineFactory::class),
            $this->createStub(ArgumentsParser::class),
        );

        $actual = $harness->run();

        $this->assertSame(1, $actual);
    }
}
