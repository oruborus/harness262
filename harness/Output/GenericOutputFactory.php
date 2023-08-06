<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Output;

use Oru\EcmaScript\Harness\Contracts\OutputConfig;
use Oru\EcmaScript\Harness\Contracts\Output;
use Oru\EcmaScript\Harness\Contracts\OutputFactory;

final readonly class GenericOutputFactory implements OutputFactory
{
    public function make(OutputConfig $config): Output
    {
        return new ConsoleOutput();
    }
}
