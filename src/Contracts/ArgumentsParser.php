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

interface ArgumentsParser
{
    public function hasOption(string $option): bool;

    public function getOption(string $option): string;

    /**
     * @return string[]
     */
    public function rest(): array;
}
