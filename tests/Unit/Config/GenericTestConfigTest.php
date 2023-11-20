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

use Oru\Harness\Config\GenericTestConfig;
use Oru\Harness\Contracts\Frontmatter;
use Oru\Harness\Contracts\TestSuiteConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericTestConfig::class)]
final class GenericTestConfigTest extends TestCase
{
    #[Test]
    public function actsAsValueObject(): void
    {
        $expectedPath            = 'path/to/file';
        $expectedContent         = 'CONTENT';
        $expectedFrontmatter     = $this->createMock(Frontmatter::class);
        $expectedTestSuiteConfig = $this->createMock(TestSuiteConfig::class);

        $actual = new GenericTestConfig(
            $expectedPath,
            $expectedContent,
            $expectedFrontmatter,
            $expectedTestSuiteConfig
        );

        $this->assertSame($expectedPath, $actual->path());
        $this->assertSame($expectedContent, $actual->content());
        $this->assertSame($expectedFrontmatter, $actual->frontmatter());
        $this->assertSame($expectedTestSuiteConfig, $actual->testSuiteConfig());
    }
}
