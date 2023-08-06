<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface OutputFactory
{
    public function make(OutputConfig $config): Output;
}
