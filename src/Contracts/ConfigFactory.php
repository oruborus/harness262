<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface ConfigFactory
{
    /**
     * @param string[] $input
     */
    public function make(array $input): Config;
}
