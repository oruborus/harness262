<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface PrinterFactory
{
    public function make(PrinterConfig $config, Output $output, int $plannedTests): Printer;
}
