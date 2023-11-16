<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface TestRunner
{
    public function add(TestConfig $config): void;

    /**
     * @return TestResult[]
     */
    public function run(): array;

    /**
     * @return TestResult[]
     */
    public function results(): array;
}
