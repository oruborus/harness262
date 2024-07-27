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

use Oru\Harness\Contracts\TestCase;

use function preg_match;

final class FileNameMatchesRegExpFilter extends BaseRegExpFilter
{
    /**
     * @param TestCase ...$values
     *
     * @return TestCase[]
     */
    public function apply(TestCase ...$testCases): array
    {
        return array_filter(
            $testCases,
            fn (TestCase $testCase): bool => (bool) preg_match($this->pattern, $testCase->path())
        );
    }
}
