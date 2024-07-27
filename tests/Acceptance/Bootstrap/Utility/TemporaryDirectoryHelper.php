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

use function array_unshift;
use function is_dir;
use function mkdir;
use function preg_split;
use function rmdir;

final class TemporaryDirectoryHelper
{
    /**
     * @var string[] $parts
     */
    private array $parts = [];

    public function __construct(string $path)
    {
        $parts = preg_split('/[\/\\\\]/', $path);

        $dir = '.';
        foreach ($parts as $part) {
            if (!is_dir($dir .= "/{$part}")) {
                array_unshift($this->parts, $dir);
                mkdir($dir);
            }
        }
    }

    public function __destruct()
    {
        foreach ($this->parts as $dir) {
            rmdir($dir);
        }
    }
}
