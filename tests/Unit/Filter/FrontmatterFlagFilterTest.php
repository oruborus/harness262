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
use Oru\Harness\Contracts\Frontmatter;
use Oru\Harness\Contracts\FrontmatterFlag;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Filter\FrontmatterFlagFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

#[CoversClass(FrontmatterFlagFilter::class)]
final class FrontmatterFlagFilterTest extends PHPUnitTestCase
{
    #[Test]
    #[DataProvider('provideFlag')]
    public function filterTestConfigsWithMatchingFlag(FrontmatterFlag $flag, array $testCases, array $expected): void
    {
        $filter = new FrontmatterFlagFilter($flag);
        $actual = $filter->apply(...$testCases);

        $this->assertSame($expected, array_values($actual));
    }

    public static function provideFlag(): Generator
    {
        $strict = static::createConfiguredStub(TestCase::class, [
            'frontmatter' => static::createConfiguredStub(Frontmatter::class, [
                'flags' => [FrontmatterFlag::onlyStrict]
            ])
        ]);
        $noStrict = static::createConfiguredStub(TestCase::class, [
            'frontmatter' => static::createConfiguredStub(Frontmatter::class, [
                'flags' => [FrontmatterFlag::noStrict]
            ])
        ]);
        $module = static::createConfiguredStub(TestCase::class, [
            'frontmatter' => static::createConfiguredStub(Frontmatter::class, [
                'flags' => [FrontmatterFlag::module]
            ])
        ]);
        $async = static::createConfiguredStub(TestCase::class, [
            'frontmatter' => static::createConfiguredStub(Frontmatter::class, [
                'flags' => [FrontmatterFlag::async]
            ])
        ]);
        $raw = static::createConfiguredStub(TestCase::class, [
            'frontmatter' => static::createConfiguredStub(Frontmatter::class, [
                'flags' => [FrontmatterFlag::raw]
            ])
        ]);

        yield 'only-strict' => [FrontmatterFlag::onlyStrict, [$strict, $noStrict, $module, $async, $raw], [$strict]];
        yield 'no-strict'   => [FrontmatterFlag::noStrict, [$strict, $noStrict, $module, $async, $raw], [$noStrict]];
        yield 'module'      => [FrontmatterFlag::module, [$strict, $noStrict, $module, $async, $raw], [$module]];
        yield 'async'       => [FrontmatterFlag::async, [$strict, $noStrict, $module, $async, $raw], [$async]];
        yield 'raw'         => [FrontmatterFlag::raw, [$strict, $noStrict, $module, $async, $raw], [$raw]];
    }
}
