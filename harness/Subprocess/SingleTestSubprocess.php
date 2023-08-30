<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Subprocess;

use Oru\EcmaScript\Harness\Contracts\Subprocess;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestResult;
use Oru\EcmaScript\Harness\Contracts\TestRunner;

/**
 * @implements Subprocess<TestResult>
 */
final class SingleTestSubprocess implements Subprocess
{
    public function __construct(
        private TestRunner $testRunner,
        private TestConfig $testConfig
    ) {
    }

    public function run(): TestResult
    {
        return $this->testRunner->run($this->testConfig);
    }
}
