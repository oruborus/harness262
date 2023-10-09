<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface TestConfigFactory
{
    /**
     * @return TestConfig[]
     */
    public function make(string $path): array;
}
