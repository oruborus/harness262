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

namespace Tests\Unit\ArgumentsParser;

use Oru\Harness\ArgumentsParser\CliArgumentsParser;
use Oru\Harness\ArgumentsParser\Exception\InvalidOptionException;
use Oru\Harness\ArgumentsParser\Exception\MissingArgumentException;
use Oru\Harness\ArgumentsParser\Exception\UnknownOptionException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CliArgumentsParser::class)]
final class CliArgumentsParserTest extends TestCase
{
    #[Test]
    public function takesShortOptionsFromArgvAndMapsToLongOption(): void
    {
        $parser = new CliArgumentsParser(['-o'], ['option' => 'o']);

        $actual = $parser->hasOption('option');

        $this->assertTrue($actual);
    }

    #[Test]
    public function throwsWhenUnknownShortOptionIsPresented(): void
    {
        $this->expectExceptionObject(new UnknownOptionException('Unknown short option `o` provided'));

        new CliArgumentsParser(['-o'], []);
    }

    #[Test]
    public function expandsMultipleShortOptions(): void
    {
        $parser = new CliArgumentsParser(
            ['-o', '-abc', '-q'],
            ['option' => 'o', 'optionA' => 'a', 'optionB' => 'b', 'optionC' => 'c', 'anotherOption' => 'q']
        );

        $actual = $parser->hasOption('optionA')
            && $parser->hasOption('optionB')
            && $parser->hasOption('optionC');

        $this->assertTrue($actual);
        $this->assertEmpty($parser->rest());
    }

    #[Test]
    public function takesLongOptionsFromArgv(): void
    {
        $parser = new CliArgumentsParser(['--option'], ['option' => null]);

        $actual = $parser->hasOption('option');

        $this->assertTrue($actual);
    }

    #[Test]
    public function throwsWhenUnknownLongOptionIsPresented(): void
    {
        $this->expectExceptionObject(new UnknownOptionException('Unknown long option `option` provided'));

        new CliArgumentsParser(['--option'], []);
    }

    #[Test]
    public function nonOptionValuesAreAggregatedInRest(): void
    {
        $parser = new CliArgumentsParser(
            ['aaa', '-o', 'bbb', '--option2', 'ccc'],
            ['option' => 'o', 'option2' => null]
        );

        $actual = $parser->hasOption('option')
            && $parser->hasOption('option2');

        $this->assertTrue($actual);
        $this->assertSame(['aaa', 'bbb', 'ccc'], $parser->rest());
    }

    #[Test]
    public function throwsWhenSingleDashIsProvided(): void
    {
        $this->expectExceptionObject(new InvalidOptionException('Invalid option `-` provided'));

        new CliArgumentsParser(['-'], []);
    }

    #[Test]
    public function throwsWhenDoubleDashIsProvided(): void
    {
        $this->expectExceptionObject(new InvalidOptionException('Invalid option `--` provided'));

        new CliArgumentsParser(['--'], []);
    }

    #[Test]
    public function failsWhenLongOptionMissesRequiredArgument(): void
    {
        $this->expectExceptionObject(new MissingArgumentException('Missing argument for option `option`'));

        new CliArgumentsParser(['--option'], ['option' => ':']);
    }

    #[Test]
    public function failsWhenLongOptionMissesRequiredArgumentAsTheNextOptionFollows(): void
    {
        $this->expectExceptionObject(new MissingArgumentException('Missing argument for option `option1`'));

        new CliArgumentsParser(['--option1', '--option2'], ['option1' => ':', 'option2' => null]);
    }

    #[Test]
    public function canGetProvidedArgumentForLongOption(): void
    {
        $parser = new CliArgumentsParser(['--option', 'argument'], ['option' => ':']);

        $actual = $parser->getOption('option');

        $this->assertSame('argument', $actual);
    }

    #[Test]
    public function throwsWhenArgumentForUnKnownOptionIsRequested(): void
    {
        $this->expectExceptionObject(new UnknownOptionException("Unknown option `option` requested"));

        $parser = new CliArgumentsParser([], []);

        $parser->getOption('option');
    }

    #[Test]
    public function throwsWhenArgumentForKnownOptionIsNotSetButRequested(): void
    {
        $this->expectExceptionObject(new MissingArgumentException("Argument for `option` was not provided"));

        $parser = new CliArgumentsParser(['--option'], ['option' => null]);

        $parser->getOption('option');
    }

    #[Test]
    public function providedArgumentForOptionIsNotInRest(): void
    {
        $parser = new CliArgumentsParser(['--option', 'argument'], ['option' => ':']);

        $actual = $parser->rest();

        $this->assertNotContains('argument', $actual);
    }
}
