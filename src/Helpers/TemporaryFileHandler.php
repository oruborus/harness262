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

use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class TemporaryFileHandler
{
    private string $path;

    public function __construct(
        string $contents
    ) {
        $this->path = tempnam(sys_get_temp_dir(), '');
        file_put_contents($this->path, $contents);
    }

    public function __destruct()
    {
        unlink($this->path);
    }

    public function path(): string
    {
        return $this->path;
    }
}
