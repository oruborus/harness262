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

namespace Tests\Utility\StreamWrapper;

final class FailToOpenStreamWrapper
{
    public function stream_open(
        string $path,
        string $mode,
        int $options,
        ?string &$opened_path
    ): bool {
        return false;
    }
}
