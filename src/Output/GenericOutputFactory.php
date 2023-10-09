<?php

declare(strict_types=1);

namespace Oru\Harness\Output;

use Oru\Harness\Contracts\OutputConfig;
use Oru\Harness\Contracts\Output;
use Oru\Harness\Contracts\OutputFactory;

final readonly class GenericOutputFactory implements OutputFactory
{
    public function make(OutputConfig $config): Output
    {
        return new ConsoleOutput();
    }
}
