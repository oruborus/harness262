<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface PrinterFactory
{
    public function make(PrinterConfig $config, Output $output): Printer;
}
