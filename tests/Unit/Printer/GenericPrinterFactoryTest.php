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

namespace Tests\Unit\Printer;

use Generator;
use Oru\Harness\Contracts\Output;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\PrinterConfig;
use Oru\Harness\Contracts\PrinterVerbosity;
use Oru\Harness\Printer\GenericPrinterFactory;
use Oru\Harness\Printer\NormalPrinter;
use Oru\Harness\Printer\SilentPrinter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericPrinterFactory::class)]
#[UsesClass(NormalPrinter::class)]
final class GenericPrinterFactoryTest extends TestCase
{
    /**
     * @param class-string<Printer> $printerClassname
     */
    #[Test]
    #[DataProvider('providePrinterConfiguration')]
    public function returnsTheCorrectPrinterClassBasedOnConfiguration(PrinterConfig $config, string $printerClassname): void
    {
        $factory = new GenericPrinterFactory();
        $output = $this->createStub(Output::class);

        $actual = $factory->make($config, $output, 0);

        $this->assertInstanceOf(Printer::class, $actual);
        $this->assertInstanceOf($printerClassname, $actual);
    }

    /**
     * @return Generator<string, array{0:PrinterConfig, 1:class-string<Printer>}>
     */
    public static function providePrinterConfiguration(): Generator
    {
        yield 'silent verbosity' => [static::createPrinterConfig(PrinterVerbosity::Silent), SilentPrinter::class];
        yield 'normal verbosity' => [static::createPrinterConfig(PrinterVerbosity::Normal), NormalPrinter::class];
    }

    #[Test]
    #[DataProvider('provideUnimplementedPrinterConfiguration')]
    public function failsOnUnimplementedPrinterConfiguration(PrinterConfig $config): void
    {
        $this->expectExceptionMessage('NOT IMPLEMENTED');

        $factory = new GenericPrinterFactory();
        $output = $this->createStub(Output::class);

        $factory->make($config, $output, 0);
    }

    /**
     * @return Generator<string, array{0:PrinterConfig, 1:class-string<Printer>}>
     */
    public static function provideUnimplementedPrinterConfiguration(): Generator
    {
        yield 'verbose verbosity' => [static::createPrinterConfig(PrinterVerbosity::Verbose)];
    }

    private static function createPrinterConfig(PrinterVerbosity $printerVerbosity): PrinterConfig
    {
        return new class($printerVerbosity) implements PrinterConfig {
            public function __construct(
                private PrinterVerbosity $printerVerbosity
            ) {}

            public function verbosity(): PrinterVerbosity
            {
                return $this->printerVerbosity;
            }
        };
    }
}
