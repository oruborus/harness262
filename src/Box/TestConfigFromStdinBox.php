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

namespace Oru\Harness\Box;

use Oru\Harness\Contracts\Box;
use Oru\Harness\Contracts\TestConfig;
use RuntimeException;

use function fopen;
use function stream_get_contents;
use function unserialize;

/**
 * @implements Box<TestConfig>
 */
final readonly class TestConfigFromStdinBox implements Box
{
    private TestConfig $testConfig;

    /**
     * @throws RuntimeException
     */
    public function __construct()
    {
        $input = fopen('php://stdin', 'r')
            ?: throw new RuntimeException('Could not open STDIN');

        $input = stream_get_contents($input)
            ?: throw new RuntimeException('Could not get contents of STDIN');

        $config = unserialize($input);

        if (!$config instanceof TestConfig) {
            throw new RuntimeException('STDIN did not contain a serialized `TestConfig` object');
        }

        $this->testConfig = $config;
    }

    public function unbox(): TestConfig
    {
        return $this->testConfig;
    }
}
