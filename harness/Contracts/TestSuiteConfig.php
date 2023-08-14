<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface TestSuiteConfig extends Config
{
    /**
     * @return string[]
     */
    public function paths(): array;

    public function cache(): bool;
}
