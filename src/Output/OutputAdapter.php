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

namespace Oru\Harness\Output;

use Oru\Harness\Contracts\Output;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class OutputAdapter implements Output
{
    public function __construct(
        private OutputInterface $outputInterface,
    ) {}

    public function write(string $content): void
    {
        $this->outputInterface->write($content);
    }

    public function writeLn(string $content): void
    {
        $this->outputInterface->writeln($content);
    }
}
