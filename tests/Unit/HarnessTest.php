<?php

/**
 * Copyright (c) 2023, Felix Jahn
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

use Generator;
use Oru\Harness\Cli\Exception\InvalidOptionException;
use Oru\Harness\Contracts\Facade;
use Oru\Harness\Harness;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Utility\Facade\TestFacade;

use const PHP_EOL;

#[CoversClass(Harness::class)]
final class HarnessTest extends TestCase
{
    public const TEMPLATE_PATH = __DIR__ . '/../../src/Template/ExecuteTest.php';

    #[Test]
    #[DataProvider('provideInvalidOptions')]
    public function failsWhenInvalidOptionIsProvided(string $invalidOption): void
    {
        $this->expectExceptionObject(new InvalidOptionException("Invalid option `{$invalidOption}` provided"));

        $harness = new Harness($this->createStub(Facade::class));

        $harness->run(['harness.php', $invalidOption]);
    }

    public static function provideInvalidOptions(): Generator
    {
        yield '-'  => ['-'];
        yield '--' => ['--'];
    }

    #[Test]
    public function informsTheUserThatProvidedRegularExpressionPatternIsMalFormed(): void
    {
        $this->expectOutputString(
            PHP_EOL . 'EcmaScript Test Harness' . PHP_EOL .
                PHP_EOL . 'The provided regular expression pattern is malformed.' .
                PHP_EOL . 'The following warning was issued:' .
                PHP_EOL . '"Compilation failed: missing closing parenthesis at offset 1"' . PHP_EOL
        );
        $harness = new Harness($this->createStub(Facade::class));

        $actual = $harness->run(['harness.php', './tests/Unit/Fixtures/TestCase/basic.js', '--include', '(']);

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
        $harness = new Harness($this->createStub(Facade::class));

        $actual = $harness->run(['harness.php', $expected]);

        $this->assertSame(1, $actual);
    }

    #[Test]
    public function informsTheUserThatNoPathsWhereProvided(): void
    {
        $this->expectOutputString(
            PHP_EOL . 'EcmaScript Test Harness' . PHP_EOL .
                PHP_EOL . 'No test path specified. Aborting.' . PHP_EOL
        );
        $harness = new Harness($this->createStub(Facade::class));

        $actual = $harness->run(['harness.php']);

        $this->assertSame(1, $actual);
    }

    // #[Test]
    public function doesNotExecuteAllTestsWhenStopOnCharacteristicIsMet(): void
    {
        $harness = new Harness(new TestFacade());

        \ob_start();
        $harness->run([
            'harness.php',
            './tests/EndToEnd/Fixtures/fail.js',
            './tests/EndToEnd/Fixtures/empty.js',
            './tests/EndToEnd/Fixtures/empty.js',
            './tests/EndToEnd/Fixtures/empty.js',
            './tests/EndToEnd/Fixtures/empty.js',
            './tests/EndToEnd/Fixtures/empty.js',
            './tests/EndToEnd/Fixtures/empty.js',
            './tests/EndToEnd/Fixtures/empty.js',
            './tests/EndToEnd/Fixtures/empty.js',
            './tests/EndToEnd/Fixtures/empty.js',
            './tests/EndToEnd/Fixtures/empty.js',
            './tests/EndToEnd/Fixtures/empty.js',
            './tests/EndToEnd/Fixtures/empty.js',
            './tests/EndToEnd/Fixtures/empty.js',
            './tests/EndToEnd/Fixtures/empty.js',
            './tests/EndToEnd/Fixtures/empty.js',
            './tests/EndToEnd/Fixtures/empty.js',
            '--stop-on-failure',
            '--debug'
        ]);
        $output = \ob_get_clean();

        $this->assertStringNotContainsString('........', $output);
        $this->assertMatchesRegularExpression('/Duration: \d\d:\d\d/', $output);
    }
}
