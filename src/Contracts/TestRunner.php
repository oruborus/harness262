<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface TestRunner
{
    public function run(TestConfig $config): void;

    /**
     * @return TestResult[]
     */
    public function finalize(): array;
}
