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

use Oru\Harness\Command\ClonedPhpCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function ini_get;
use function ini_restore;
use function ini_set;

#[CoversClass(ClonedPhpCommand::class)]
final class ClonedPhpCommandTest extends TestCase
{
    #[Test]
    public function replicatesDeclarationsMadeToCommand(): void
    {
        $setting = (int) ini_get('max_execution_time') + 10;
        ini_set('max_execution_time', $setting);

        $actual = (string) new ClonedPhpCommand('');

        ini_restore('max_execution_time');

        $this->assertStringStartsWith('php ', $actual);
        $this->assertStringContainsString("-d \"max_execution_time={$setting}\"", $actual);
    }

    #[Test]
    public function endsWithProvidedSuffix(): void
    {
        $expected = 'COMMAND_SUFFIX';

        $actual = (string) new ClonedPhpCommand($expected);

        $this->assertStringEndsWith($expected, $actual);
    }
}
