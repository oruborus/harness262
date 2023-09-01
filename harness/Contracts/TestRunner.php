<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface TestRunner
{
    public function run(TestConfig $config): TestResult;

    public function finalize(): void;
}
