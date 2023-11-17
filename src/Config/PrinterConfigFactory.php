<?php

declare(strict_types=1);

namespace Oru\Harness\Config;

use Oru\Harness\Contracts\ArgumentsParser;
use Oru\Harness\Contracts\ConfigFactory;
use Oru\Harness\Contracts\PrinterConfig;
use Oru\Harness\Contracts\PrinterVerbosity;

final readonly class PrinterConfigFactory implements ConfigFactory
{
    public function __construct(
        private ArgumentsParser $argumentsParser
    ) {}

    public function make(): PrinterConfig
    {
        $verbosity = PrinterVerbosity::Normal;
        if (
            $this->argumentsParser->hasOption('verbose')
            && !$this->argumentsParser->hasOption('silent')
        ) {
            $verbosity = PrinterVerbosity::Verbose;
        }
        if (
            $this->argumentsParser->hasOption('silent')
            && !$this->argumentsParser->hasOption('verbose')
        ) {
            $verbosity = PrinterVerbosity::Silent;
        }

        return new class (
            $verbosity
        ) implements PrinterConfig {
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
