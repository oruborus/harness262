<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

use Oru\EcmaScript\Core\Contracts\Engine;

interface TestRunner
{
    public function run(TestConfig $config): TestResult;

    public static function executeTest(Engine $engine, TestConfig $config, AssertionFactory $assertionFactory): TestResult;
}
