<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface OutputFactory
{
    public function make(OutputConfig $config): Output;
}
