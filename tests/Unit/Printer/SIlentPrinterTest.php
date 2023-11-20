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

namespace Tests\Unit\Printer;

use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Printer\SilentPrinter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SilentPrinter::class)]
final class SilentPrinterTest extends TestCase
{
    #[Test]
    public function printsNothing(): void
    {
        $printer = new SilentPrinter();

        $this->assertNull($printer->writeLn('Test'));
        $this->assertNull($printer->start());
        $this->assertNull($printer->setStepCount(231649));
        $this->assertNull($printer->step(TestResultState::Success));
        $this->assertNull($printer->end([], 0));
    }
}
