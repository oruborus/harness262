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

namespace Tests\Unit\TestCase;

use Oru\Harness\Contracts\Frontmatter;
use Oru\Harness\Contracts\ImplicitStrictness;
use Oru\Harness\Contracts\TestSuite;
use Oru\Harness\TestCase\GenericTestCase;
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
        $expectedTestSuiteMock      = $this->createMock(TestSuite::class);
        $expectedImplicitStrictness = ImplicitStrictness::Unknown;

        $actual = new GenericTestCase(
            $expectedPath,
            $expectedContent,
            $expectedFrontmatter,
            $expectedTestSuiteMock,
            $expectedImplicitStrictness,
        );

        $this->assertSame($expectedPath, $actual->path());
        $this->assertSame($expectedContent, $actual->content());
        $this->assertSame($expectedFrontmatter, $actual->frontmatter());
        $this->assertSame($expectedTestSuiteMock, $actual->testSuite());
        $this->assertSame($expectedImplicitStrictness, $actual->implicitStrictness());
    }
}
