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

namespace Tests\Utility\Helpers;

use function stream_wrapper_register;
use function stream_wrapper_restore;
use function stream_wrapper_unregister;

final class StreamWrapperOverride
{
    public function __construct(
        private string $protocol,
        string $className,
    ) {
        stream_wrapper_unregister($this->protocol);
        stream_wrapper_register($this->protocol, $className);
    }

    public function __destruct()
    {
        stream_wrapper_restore($this->protocol);
    }
}
