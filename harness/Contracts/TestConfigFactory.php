<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface TestConfigFactory
{
    /**
     * @return TestConfig[]
     */
    public function make(string $path): array;
}
