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

use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Filter\FileNameMatchesRegExpFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

#[CoversClass(FileNameMatchesRegExpFilter::class)]
final class FileNameMatchesRegExpFilterTest extends PHPUnitTestCase
{
    #[Test]
    public function filterTestConfigWithAllNonMatchingFilenames(): void
    {
        $count = 5;
        $testCaseStubs = [];
        $expected = [];
        for ($i = 0; $i < $count; $i++) {
            $testCaseStub = $this->createConfiguredStub(TestCase::class, ['path' => "path{$i}"]);
            $testCaseStubs[] = $testCaseStub;
            if ($i === 2 || $i === 3) {
                $expected[] = $testCaseStub;
            }
        }

        $filter = new FileNameMatchesRegExpFilter('[23]');
        $actual = $filter->apply(...$testCaseStubs);

        $this->assertSame($expected, array_values($actual));
    }
}
