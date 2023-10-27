<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Oru\Harness\Config\PrinterConfigFactory;
use Oru\Harness\Contracts\ArgumentsParser;
use Oru\Harness\Contracts\PrinterConfig;
use Oru\Harness\Contracts\PrinterVerbosity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_key_exists;

#[CoversClass(PrinterConfigFactory::class)]
final class PrinterConfigFactoryTest extends TestCase
{
    /**
     * @param array<string, ?string> $options
     * @param list<string> $rest
     */
    private function createArgumentsParserStub(array $options = [], array $rest = []): ArgumentsParser
    {
        return new class($options, $rest) implements ArgumentsParser
        {
            /**
             * @param array<string, ?string> $options
             * @param list<string> $rest
             */
            public function __construct(
                private array $options,
                private array $rest
            ) {
            }

            public function hasOption(string $option): bool
            {
                return array_key_exists($option, $this->options);
            }

            public function getOption(string $option): string
            {
                return $this->options[$option] ?? '';
            }

            public function rest(): array
            {
                return $this->rest;
            }
        };
    }

    #[Test]
    public function createsConfigPrinter(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub();
        $factory = new PrinterConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertInstanceOf(PrinterConfig::class, $actual);
    }


    #[Test]
    public function defaultConfigForVerbosityIsNormal(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub();
        $factory = new PrinterConfigFactory($argumentsParserStub);

        $actual = $factory->make([]);

        $this->assertSame(PrinterVerbosity::Normal, $actual->verbosity());
    }

    #[Test]
    public function defaultConfigForVerbosityCanBeSetToSilent(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub(['silent' => null]);
        $factory = new PrinterConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame(PrinterVerbosity::Silent, $actual->verbosity());
    }

    #[Test]
    public function defaultConfigForVerbosityCanBeSetToVerbose(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub(['verbose' => null]);
        $factory = new PrinterConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame(PrinterVerbosity::Verbose, $actual->verbosity());
    }

    #[Test]
    public function mixedVerbosityOptionsCancelOutToNormal(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub(['silent' => null, 'verbose' => null]);
        $factory = new PrinterConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame(PrinterVerbosity::Normal, $actual->verbosity());
    }
}
