<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface TestRunnerFactory
{
    public function make(TestSuiteConfig $config): TestRunner;
}
