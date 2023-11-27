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

namespace Tests\Unit\Config;

use Oru\Harness\Config\GenericTestCase;
use Oru\Harness\Contracts\Frontmatter;
use Oru\Harness\Contracts\ImplicitStrictness;
use Oru\Harness\Contracts\TestSuiteConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericTestCase::class)]
final class GenericTestCaseTest extends TestCase
{
    #[Test]
    public function actsAsValueObject(): void
    {
        $expectedPath               = 'path/to/file';
        $expectedContent            = 'CONTENT';
        $expectedFrontmatter        = $this->createMock(Frontmatter::class);
        $expectedTestSuiteConfig    = $this->createMock(TestSuiteConfig::class);
        $expectedImplicitStrictness = ImplicitStrictness::Unknown;

        $actual = new GenericTestCase(
            $expectedPath,
            $expectedContent,
            $expectedFrontmatter,
            $expectedTestSuiteConfig,
            $expectedImplicitStrictness,
        );

        $this->assertSame($expectedPath, $actual->path());
        $this->assertSame($expectedContent, $actual->content());
        $this->assertSame($expectedFrontmatter, $actual->frontmatter());
        $this->assertSame($expectedTestSuiteConfig, $actual->testSuiteConfig());
        $this->assertSame($expectedImplicitStrictness, $actual->implicitStrictness());
    }
}
