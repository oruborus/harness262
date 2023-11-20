<?php

/**
 * Copyright (c) 2023, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Oru\Harness\Subprocess;

use Oru\Harness\Contracts\Subprocess;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestRunner;
use Oru\Harness\Subprocess\Exception\InvalidReturnValueException;

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
    ) {}

    /**
     * @throws InvalidReturnValueException
     */
    public function run(): TestResult
    {
        $this->testRunner->add($this->testConfig);

        $result = $this->testRunner->run();

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
