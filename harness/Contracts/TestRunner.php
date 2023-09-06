<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface TestRunner
{
    public function run(TestConfig $config): void;

    /**
     * @return TestResult[]
     */
    public function finalize(): array;
}
