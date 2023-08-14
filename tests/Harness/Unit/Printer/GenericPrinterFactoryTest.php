<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Printer;

use Generator;
use Oru\EcmaScript\Harness\Contracts\Output;
use Oru\EcmaScript\Harness\Contracts\Printer;
use Oru\EcmaScript\Harness\Contracts\PrinterConfig;
use Oru\EcmaScript\Harness\Contracts\PrinterVerbosity;
use Oru\EcmaScript\Harness\Printer\GenericPrinterFactory;
use Oru\EcmaScript\Harness\Printer\NormalPrinter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericPrinterFactory::class)]
final class GenericPrinterFactoryTest extends TestCase
{
    /**
     * @test
     * @dataProvider providePrinterConfiguration
     *
     * @param class-string<Printer> $printerClassname
     */
    public function returnsTheCorrectPrinterClassBasedOnConfiguration(PrinterConfig $config, string $printerClassname): void
    {
        $factory = new GenericPrinterFactory();
        $output = $this->createMock(Output::class);

        $actual = $factory->make($config, $output, 0);

        $this->assertInstanceOf(Printer::class, $actual);
        $this->assertInstanceOf($printerClassname, $actual);
    }

    /**
     * @return Generator<string, array{0:PrinterConfig, 1:class-string<Printer>}>
     */
    public static function providePrinterConfiguration(): Generator
    {
        yield 'normal verbosity' => [static::createPrinterConfig(PrinterVerbosity::Normal), NormalPrinter::class];
    }

    /**
     * @test
     * @dataProvider provideUnimplementedPrinterConfiguration
     */
    public function failsOnUnimplementedPrinterConfiguration(PrinterConfig $config): void
    {
        $this->expectExceptionMessage('NOT IMPLEMENTED');

        $factory = new GenericPrinterFactory();
        $output = $this->createMock(Output::class);

        $factory->make($config, $output, 0);
    }

    /**
     * @return Generator<string, array{0:PrinterConfig, 1:class-string<Printer>}>
     */
    public static function provideUnimplementedPrinterConfiguration(): Generator
    {
        yield 'silent verbosity' => [static::createPrinterConfig(PrinterVerbosity::Silent)];
        yield 'verbose verbosity' => [static::createPrinterConfig(PrinterVerbosity::Verbose)];
    }

    private static function createPrinterConfig(PrinterVerbosity $printerVerbosity): PrinterConfig
    {
        return new class($printerVerbosity) implements PrinterConfig
        {
            public function __construct(
                private PrinterVerbosity $printerVerbosity
            ) {
            }

            public function verbosity(): PrinterVerbosity
            {
                return $this->printerVerbosity;
            }
        };
    }
}
