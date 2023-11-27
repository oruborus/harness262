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

namespace Oru\Harness\Config;

use Oru\Harness\Contracts\Frontmatter;
use Oru\Harness\Contracts\ImplicitStrictness;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestSuite;

final readonly class GenericTestCase implements TestCase
{
    public function __construct(
        private string $path,
        private string $content,
        private Frontmatter $frontmatter,
        private TestSuite $testSuite,
        private ImplicitStrictness $implicitStrictness,
    ) {}

    public function path(): string
    {
        return $this->path;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function frontmatter(): Frontmatter
    {
        return $this->frontmatter;
    }

    public function testSuite(): TestSuite
    {
        return $this->testSuite;
    }

    public function implicitStrictness(): ImplicitStrictness
    {
        return $this->implicitStrictness;
    }
}
