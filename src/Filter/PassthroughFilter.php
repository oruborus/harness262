<?php

/**
 * Copyright (c) 2023-2025, Felix Jahn
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

use Oru\Harness\Contracts\Filter;
use Oru\Harness\Contracts\TestCase;

final readonly class PassthroughFilter implements Filter
{
    /**
     * @param TestCase ...$testCases
     *
     * @return TestCase[]
     */
    public function apply(TestCase ...$testCases): array
    {
        return $testCases;
    }
}
