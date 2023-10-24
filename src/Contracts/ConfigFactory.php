<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface ConfigFactory
{
    public function make(): Config;
}
