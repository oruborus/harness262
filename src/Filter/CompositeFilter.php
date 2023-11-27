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

use Oru\Harness\Contracts\Filter;
use Oru\Harness\Contracts\TestCase;

final readonly class CompositeFilter implements Filter
{
    /**
     *
     * @param Filter[] $filters
     */
    public function __construct(
        private array $filters
    ) {}

    /**
     * @param TestCase ...$values
     *
     * @return TestCase[]
     */
    public function apply(TestCase ...$testCases): array
    {
        foreach ($this->filters as $filter) {
            $testCases = $filter->apply(...$testCases);
        }

        return $testCases;
    }

}
