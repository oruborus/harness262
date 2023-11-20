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

namespace Oru\Harness\Contracts;

use Throwable;

interface TestResult
{
    public function state(): TestResultState;

    public function path(): string;

    /**
     * @return string[]
     */
    public function usedFiles(): array;

    public function duration(): int;

    public function throwable(): ?Throwable;
}
