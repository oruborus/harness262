<?php

declare(strict_types=1);

namespace Oru\Harness\Config;

use Oru\Harness\Contracts\ArgumentsParser;
use Oru\Harness\Contracts\ConfigFactory;
use Oru\Harness\Contracts\OutputConfig;
use Oru\Harness\Contracts\OutputType;

final readonly class OutputConfigFactory implements ConfigFactory
{
    public function __construct(
        private ArgumentsParser $argumentsParser
    ) {}

    public function make(): OutputConfig
    {
        return new class () implements OutputConfig {
            /**
             * @return OutputType[]
             */
            public function outputTypes(): array
            {
                return [OutputType::Console];
            }
        };
    }
}
