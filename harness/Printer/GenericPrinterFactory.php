<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Printer;

use Oru\EcmaScript\Harness\Contracts\Output;
use Oru\EcmaScript\Harness\Contracts\PrinterConfig;
use Oru\EcmaScript\Harness\Contracts\Printer;
use Oru\EcmaScript\Harness\Contracts\PrinterFactory;
use Oru\EcmaScript\Harness\Contracts\PrinterVerbosity;

final readonly class GenericPrinterFactory implements PrinterFactory
{
    public function make(PrinterConfig $config, Output $output, int $plannedTests): Printer
    {
        return match ($config->verbosity()) {
            PrinterVerbosity::Normal => new NormalPrinter($output),
            default => throw new \RuntimeException('NOT IMPLEMENTED')
        };
    }
}
