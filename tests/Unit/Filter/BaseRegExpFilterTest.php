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

namespace Tests\Unit\Filter;

use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Filter\BaseRegExpFilter;
use Oru\Harness\Filter\Exception\MalformedRegularExpressionPatternException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(BaseRegExpFilter::class)]
final class BaseRegExpFilterTest extends TestCase
{
    #[Test]
    public function failsWhenProvidedRegularExpressionPatternIsNotValid(): void
    {
        try {
            new class ('(') extends BaseRegExpFilter {
                /**
                 * @param TestConfig ...$values
                 *
                 * @return TestConfig[]
                 */
                public function apply(TestConfig ...$testConfigs): array
                {
                    return [];
                }
            };
        } catch (MalformedRegularExpressionPatternException $expectedException) {
            $this->assertSame('Compilation failed: missing closing parenthesis at offset 1', $expectedException->getMessage());
            return;
        }

        $this->fail('Failed to assert that exception of type "MalformedRegularExpressionPatternException" is thrown');

    }
}
