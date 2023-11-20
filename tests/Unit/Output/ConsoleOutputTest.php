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

namespace Tests\Unit\Output;

use Oru\Harness\Output\ConsoleOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use const PHP_EOL;

#[CoversClass(ConsoleOutput::class)]
final class ConsoleOutputTest extends TestCase
{
    #[Test]
    public function writesCorrectly(): void
    {
        $expected = 'Hello World!';
        $this->expectOutputString($expected);

        $output = new ConsoleOutput();
        $output->write($expected);
    }

    #[Test]
    public function writesLinesCorrectly(): void
    {
        $expected = 'Hello World!';
        $this->expectOutputString($expected . PHP_EOL);

        $output = new ConsoleOutput();
        $output->writeLn($expected);
    }
}
