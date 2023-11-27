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
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Filter\CompositeFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

#[CoversClass(CompositeFilter::class)]
final class CompositeFilterTest extends PHPUnitTestCase
{
    #[Test]
    public function appliesAllProvidedFilters(): void
    {
        $count = 5;
        $testCases = [];
        for($i = 0; $i < $count; $i++) {
            $testCases[] = $this->createStub(TestCase::class);
        }
        $filterMocks = [];
        for($i = 0; $i < $count; $i++) {
            $filterMock = $this->createMock(Filter::class);
            $filterMock->expects($this->once())->method('apply')->with(...$testCases)->willReturn($testCases);
            $filterMocks[] = $filterMock;
        }

        $filter = new CompositeFilter($filterMocks);
        $actual = $filter->apply(...$testCases);

        $this->assertSame($testCases, $actual);
    }
}
