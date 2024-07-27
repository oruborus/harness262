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

namespace Tests\Acceptance\Bootstrap\Utility;

use function file_put_contents;
use function unlink;

final class NamedTemporaryFileHelper
{
    public function __construct(
        private string $path,
        string $contents
    ) {
        file_put_contents($this->path, $contents);
    }

    public function __destruct()
    {
        unlink($this->path);
    }
}
