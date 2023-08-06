<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface ConfigFactory
{
    /**
     * @param string[] $input
     */
    public function make(array $input): Config;
}
