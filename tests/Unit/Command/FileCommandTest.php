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

namespace Tests\Unit\Command;

use Oru\Harness\Command\FileCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileCommand::class)]
final class FileCommandTest extends TestCase
{
    #[Test]
    public function encapsulatesFilePath(): void
    {
        $expected = 'PATH';

        $actual = (string) new FileCommand($expected);

        $this->assertStringEndsWith($expected, $actual);
    }
}
