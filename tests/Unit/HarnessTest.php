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

namespace Tests\Unit;

use Oru\Harness\Contracts\ArgumentsParser;
use Oru\Harness\Contracts\EngineFactory;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Contracts\TestSuite;
use Oru\Harness\Contracts\TestSuiteFactory;
use Oru\Harness\Harness;
use Oru\Harness\TestSuite\Exception\InvalidPathException;
use Oru\Harness\TestSuite\Exception\MissingPathException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Harness::class)]
final class HarnessTest extends TestCase
{
    public const TEMPLATE_PATH = __DIR__ . '/../../src/Template/ExecuteTest.php';

    private function createHarness(
        ?TestSuiteFactory $testSuiteFactory = null,
        ?EngineFactory $engineFactory = null,
        ?ArgumentsParser $argumentsParser = null,
        ?Printer $printer = null,
    ): Harness {
        if (is_null($testSuiteFactory)) {
            $testSuiteFactory = $this->createConfiguredStub(TestSuiteFactory::class, [
                'make' => $this->createCOnfiguredStub(TestSuite::class, [
                    'testRunnerMode' => TestRunnerMode::Linear,
                ]),
            ]);
        }

        return new Harness(
            $testSuiteFactory,
            $engineFactory ?? $this->createStub(EngineFactory::class),
            $argumentsParser ?? $this->createStub(ArgumentsParser::class),
            $printer ?? $this->createStub(Printer::class),
        );
    }

    #[Test]
    public function informsTheUserThatProvidedRegularExpressionPatternIsMalFormed(): void
    {
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->once())->method('start');
        $printerMock->expects($this->exactly(3))->method('writeLn')->willReturnCallback(
            function (string $actual): void {
                static $count = 0;
                $expected  = match ($count++) {
                    0 => 'The provided regular expression pattern is malformed.',
                    1 => 'The following warning was issued:',
                    2 => '"Compilation failed: missing closing parenthesis at offset 1"',
                };

                $this->assertSame($expected, $actual);
            }
        );
        $argumentsParserStub = $this->createConfiguredStub(ArgumentsParser::class, ['rest' => ['./tests/Unit/Fixtures/TestCase/basic.js']]);
        $argumentsParserStub->method('getOption')->willReturnCallback(fn(string $option): string => $option === 'include' ? '(' : '');
        $argumentsParserStub->method('hasOption')->willReturnCallback(fn(string $option): bool => $option === 'include');
        $harness = $this->createHarness(
            argumentsParser: $argumentsParserStub,
            printer: $printerMock,
        );

        $actual = $harness->run();

        $this->assertSame(1, $actual);
    }

    #[Test]
    public function informsTheUserThatProvidedPathIsInvalid(): void
    {
        $expected = 'Exception message';

        $testSuiteFactoryStub = $this->createStub(TestSuiteFactory::class);
        $testSuiteFactoryStub->method('make')->willThrowException(new InvalidPathException($expected));

        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->once())->method('start');
        $printerMock->expects($this->once())->method('writeLn')->with($expected);
        $harness = $this->createHarness(
            testSuiteFactory: $testSuiteFactoryStub,
            argumentsParser: $this->createConfiguredStub(
                ArgumentsParser::class,
                ['rest' => [$expected]],
            ),
            printer: $printerMock,
        );

        $actual = $harness->run();

        $this->assertSame(1, $actual);
    }

    #[Test]
    public function informsTheUserThatNoPathsWhereProvided(): void
    {
        $expected = 'Exception message';

        $testSuiteFactoryStub = $this->createStub(TestSuiteFactory::class);
        $testSuiteFactoryStub->method('make')->willThrowException(new MissingPathException($expected));

        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->once())->method('start');
        $printerMock->expects($this->once())->method('writeLn')->with($expected);

        $harness = $this->createHarness(
            testSuiteFactory: $testSuiteFactoryStub,
            printer: $printerMock,
        );

        $actual = $harness->run();

        $this->assertSame(1, $actual);
    }
}
