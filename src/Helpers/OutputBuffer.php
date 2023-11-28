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

namespace Oru\Harness\Helpers;

use Stringable;

use function ob_start;
use function ob_end_clean;
use function ob_get_contents;

final readonly class OutputBuffer implements Stringable
{
    public function __construct()
    {
        ob_start();
    }

    public function __destruct()
    {
        ob_end_clean();
    }

    public function __toString(): string
    {
        /**
         * @psalm-ignore-falsable-return  Output buffering is guaranteed to be active.
         */
        return ob_get_contents();
    }
}
