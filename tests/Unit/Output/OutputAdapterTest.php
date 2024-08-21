<?php

/**
 * Copyright (c) 2024, Felix Jahn
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

use Oru\Harness\Output\OutputAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;


#[CoversClass(OutputAdapter::class)]
final class OutputAdapterTest extends TestCase
{
    #[Test]
    public function proxiesOutputInterfaceMethods(): void
    {
        $outputInterfaceMock = $this->createMock(OutputInterface::class);
        $outputInterfaceMock->expects($this->once())->method('write')->with('write');
        $outputInterfaceMock->expects($this->once())->method('writeLn')->with('writeLn');

        $outputAdapter = new OutputAdapter($outputInterfaceMock);

        $outputAdapter->write('write');
        $outputAdapter->writeLn('writeLn');
    }
}
