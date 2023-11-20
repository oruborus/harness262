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

namespace Oru\Harness\Printer;

use Oru\Harness\Contracts\Output;
use Oru\Harness\Contracts\PrinterConfig;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\PrinterFactory;
use Oru\Harness\Contracts\PrinterVerbosity;

final readonly class GenericPrinterFactory implements PrinterFactory
{
    public function make(PrinterConfig $config, Output $output): Printer
    {
        return match ($config->verbosity()) {
            PrinterVerbosity::Silent => new SilentPrinter(),
            PrinterVerbosity::Normal => new NormalPrinter($output),
            default => throw new \RuntimeException('NOT IMPLEMENTED')
        };
    }
}
