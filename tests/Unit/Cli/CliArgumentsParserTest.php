<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use Oru\Harness\Cli\CliArgumentsParser;
use Oru\Harness\Cli\Exception\InvalidOptionException;
use Oru\Harness\Cli\Exception\UnknownOptionException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CliArgumentsParser::class)]
final class CliArgumentsParserTest extends TestCase
{
    #[Test]
    public function takesShortOptionsFromArgvAndMapsToLongOption(): void
    {
        $parser = new CliArgumentsParser(
            ['-o'],
            ['option' => 'o']
        );

        $actual = $parser->hasOption('option');

        $this->assertTrue($actual);
    }

    #[Test]
    public function throwsWhenUnknownShortOptionIsPresented(): void
    {
        $this->expectExceptionObject(new UnknownOptionException('Unknown short option `o` provided'));

        new CliArgumentsParser(
            ['-o'],
            []
        );
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
        $parser = new CliArgumentsParser(
            ['--option'],
            ['option' => null]
        );

        $actual = $parser->hasOption('option');

        $this->assertTrue($actual);
    }

    #[Test]
    public function throwsWhenUnknownLongOptionIsPresented(): void
    {
        $this->expectExceptionObject(new UnknownOptionException('Unknown long option `option` provided'));

        new CliArgumentsParser(
            ['--option'],
            []
        );
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

        new CliArgumentsParser(
            ['-'],
            []
        );
    }

    #[Test]
    public function throwsWhenDoubleDashIsProvided(): void
    {
        $this->expectExceptionObject(new InvalidOptionException('Invalid option `--` provided'));

        new CliArgumentsParser(
            ['--'],
            []
        );
    }
}
