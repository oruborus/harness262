<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Oru\Harness\Config\PrinterConfigFactory;
use Oru\Harness\Contracts\PrinterConfig;
use Oru\Harness\Contracts\PrinterVerbosity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Utility\ArgumentsParser\ArgumentsParserStub;

#[CoversClass(PrinterConfigFactory::class)]
final class PrinterConfigFactoryTest extends TestCase
{
    #[Test]
    public function createsConfigPrinter(): void
    {
        $argumentsParserStub = new ArgumentsParserStub();
        $factory = new PrinterConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertInstanceOf(PrinterConfig::class, $actual);
    }


    #[Test]
    public function defaultConfigForVerbosityIsNormal(): void
    {
        $argumentsParserStub = new ArgumentsParserStub();
        $factory = new PrinterConfigFactory($argumentsParserStub);

        $actual = $factory->make([]);

        $this->assertSame(PrinterVerbosity::Normal, $actual->verbosity());
    }

    #[Test]
    public function defaultConfigForVerbosityCanBeSetToSilent(): void
    {
        $argumentsParserStub = new ArgumentsParserStub(['silent' => null]);
        $factory = new PrinterConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame(PrinterVerbosity::Silent, $actual->verbosity());
    }

    #[Test]
    public function defaultConfigForVerbosityCanBeSetToVerbose(): void
    {
        $argumentsParserStub = new ArgumentsParserStub(['verbose' => null]);
        $factory = new PrinterConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame(PrinterVerbosity::Verbose, $actual->verbosity());
    }

    #[Test]
    public function mixedVerbosityOptionsCancelOutToNormal(): void
    {
        $argumentsParserStub = new ArgumentsParserStub(['silent' => null, 'verbose' => null]);
        $factory = new PrinterConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame(PrinterVerbosity::Normal, $actual->verbosity());
    }
}
