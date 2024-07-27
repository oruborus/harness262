<?php

/**
 * Copyright (c) 2023-2024, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Tests\Unit\EngineFactory;

use Oru\Harness\EngineFactory\GenericEngineFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function realpath;

#[CoversClass(GenericEngineFactory::class)]
final class GenericEngineFactoryTest extends TestCase
{
    #[Test]
    public function makesANewEngine(): void
    {
        $path = realpath('tests/Unit/Fixtures/harness.php');
        $engineFactory = new GenericEngineFactory($path);

        $expected = $engineFactory->make();
        $actual   = $engineFactory->make();

        $this->assertNotSame($expected, $actual);
    }

    #[Test]
    public function returnsThePathOfTheIncludedFile(): void
    {
        $expected = 'som/path/to/a/file';
        $engineFactory = new GenericEngineFactory($expected);

        $actual = $engineFactory->path();

        $this->assertSame($expected, $actual);
    }
}
