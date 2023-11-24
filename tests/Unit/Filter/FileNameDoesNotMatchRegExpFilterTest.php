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
use Oru\Harness\Filter\FileNameDoesNotMatchRegExpFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileNameDoesNotMatchRegExpFilter::class)]
final class FileNameDoesNotMatchRegExpFilterTest extends TestCase
{
    #[Test]
    public function filterTestConfigWithAllNonMatchingFilenames(): void
    {
        $count = 5;
        $testConfigStubs = [];
        $expected = [];
        for($i = 0; $i < $count; $i++) {
            $testConfigStub = $this->createConfiguredStub(TestConfig::class, ['path' => "path{$i}"]);
            $testConfigStubs[] = $testConfigStub;
            if ($i !== 2 && $i !== 3) {
                $expected[] = $testConfigStub;
            }
        }

        $filter = new FileNameDoesNotMatchRegExpFilter('[23]');
        $actual = $filter->apply(...$testConfigStubs);

        $this->assertSame($expected, array_values($actual));
    }
}
