<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Subprocess;

use Oru\EcmaScript\Harness\Contracts\Subprocess;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestResult;
use Oru\EcmaScript\Harness\Contracts\TestRunner;
use Oru\EcmaScript\Harness\Subprocess\Exception\InvalidReturnValueException;

use function array_shift;
use function count;

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

    /**
     * @throws InvalidReturnValueException
     */
    public function run(): TestResult
    {
        $this->testRunner->run($this->testConfig);

        $result = $this->testRunner->finalize();

        $resultCount = count($result);

        if ($resultCount < 1) {
            throw new InvalidReturnValueException('Test runner returned no test result');
        }

        if ($resultCount > 1) {
            throw new InvalidReturnValueException('Test runner returned more than one test result');
        }

        return array_shift($result);
    }
}
