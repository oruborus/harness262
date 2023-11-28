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

namespace Tests\Unit\Helpers;

use Oru\Harness\Helpers\OutputBuffer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function ob_get_level;

#[CoversClass(OutputBuffer::class)]
final class OutputBufferTest extends TestCase
{
    #[Test]
    public function buffersOutput(): void
    {
        $expected = 'lorem ipsum...';
        $outputBuffer = new OutputBuffer();

        echo $expected;

        $this->assertSame($expected, (string) $outputBuffer);
    }

    #[Test]
    public function stopsOutputBufferingWhenUnset(): void
    {
        $level = ob_get_level();
        $content = 'lorem ipsum...';
        $outputBuffer = new OutputBuffer();

        echo $content;

        unset($outputBuffer);

        $actual = $level - ob_get_level();

        $this->assertSame(0, $actual);
    }
}
