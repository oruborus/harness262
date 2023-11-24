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

namespace Oru\Harness\Filter;

use Oru\Harness\Contracts\TestConfig;

use function preg_match;

final class FileNameDoesNotMatchRegExpFilter extends BaseRegExpFilter
{
    /**
     * @param TestConfig ...$values
     *
     * @return TestConfig[]
     */
    public function apply(TestConfig ...$testConfigs): array
    {
        return array_filter(
            $testConfigs,
            fn(TestConfig $testConfig): bool => !preg_match($this->pattern, $testConfig->path())
        );
    }

}
