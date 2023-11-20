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

namespace Oru\Harness\Cache;

use Oru\Harness\Contracts\CacheRepository;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestResult;

final readonly class NoCacheRepository implements CacheRepository
{
    public function get(TestConfig $config): null
    {
        return null;
    }

    public function set(TestConfig $config, TestResult $result): void {}
}
