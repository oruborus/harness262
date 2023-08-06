<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface OutputConfig extends Config
{
    /**
     * @return OutputType[]
     */
    public function outputTypes(): array;
}
