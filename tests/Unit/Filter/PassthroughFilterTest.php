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
use Oru\Harness\Filter\PassthroughFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PassthroughFilter::class)]
final class PassthroughFilterTest extends TestCase
{
    #[Test]
    public function doesNotAlterTheProvidedListOfTestConfigs(): void
    {
        $count = 5;
        $expected = [];
        for($i = 0; $i < $count; $i++) {
            $expected[] = $this->createStub(TestConfig::class);
        }

        $filter = new PassthroughFilter();
        $actual = $filter->apply(...$expected);

        $this->assertSame($expected, $actual);
    }
}
