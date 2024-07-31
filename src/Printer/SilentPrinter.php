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

namespace Oru\Harness\Printer;

use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\TestResultState;

final class SilentPrinter implements Printer
{
    public function setStepCount(int $stepCount): void {}

    public function writeLn(string $line): void {}

    public function newLine(): void {}

    public function start(): void {}

    public function step(TestResultState $state): void {}

    public function end(array $testResults, int $duration): void {}
}
