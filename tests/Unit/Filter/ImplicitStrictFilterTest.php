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

use Generator;
use Oru\Harness\Contracts\ImplicitStrictness;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Filter\ImplicitStrictFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_pop;

#[CoversClass(ImplicitStrictFilter::class)]
final class ImplicitStrictFilterTest extends TestCase
{
    #[Test]
    #[DataProvider('provideImplicitStrictness')]
    public function filterTestConfigWithAllNonMatchingFilenames(ImplicitStrictness $implicitStrictness): void
    {
        $testConfigStubs = [];
        $testConfigStubs[] = $this->createConfiguredStub(TestConfig::class, ['implicitStrictness' => ImplicitStrictness::Strict]);
        $testConfigStubs[] = $this->createConfiguredStub(TestConfig::class, ['implicitStrictness' => ImplicitStrictness::Loose]);
        $testConfigStubs[] = $this->createConfiguredStub(TestConfig::class, ['implicitStrictness' => ImplicitStrictness::Unknown]);

        $filter = new ImplicitStrictFilter($implicitStrictness);
        $actual = $filter->apply(...$testConfigStubs);

        $this->assertCount(1, $actual);
        $actual = array_pop($actual);
        $this->assertSame($implicitStrictness, $actual->implicitStrictness());
    }

    public static function provideImplicitStrictness(): Generator
    {
        yield 'strict'  => [ImplicitStrictness::Strict];
        yield 'loose'   => [ImplicitStrictness::Loose];
        yield 'unknown' => [ImplicitStrictness::Unknown];
    }
}
