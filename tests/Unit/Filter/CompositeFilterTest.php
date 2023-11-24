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

use Oru\Harness\Contracts\Filter;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Filter\CompositeFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CompositeFilter::class)]
final class CompositeFilterTest extends TestCase
{
    #[Test]
    public function appliesAllProvidedFilters(): void
    {
        $count = 5;
        $testConfigs = [];
        for($i = 0; $i < $count; $i++) {
            $testConfigs[] = $this->createStub(TestConfig::class);
        }
        $filterMocks = [];
        for($i = 0; $i < $count; $i++) {
            $filterMock = $this->createMock(Filter::class);
            $filterMock->expects($this->once())->method('apply')->with(...$testConfigs)->willReturn($testConfigs);
            $filterMocks[] = $filterMock;
        }

        $filter = new CompositeFilter($filterMocks);
        $actual = $filter->apply(...$testConfigs);

        $this->assertSame($testConfigs, $actual);
    }
}
