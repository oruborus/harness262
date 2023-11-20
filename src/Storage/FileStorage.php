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

namespace Oru\Harness\Storage;

use Oru\Harness\Contracts\Storage;
use RuntimeException;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_string;
use function mkdir;

use const DIRECTORY_SEPARATOR;

/**
 * @implements Storage<string>
 */
final readonly class FileStorage implements Storage
{
    public function __construct(
        private string $basePath
    ) {
        if (!file_exists($this->basePath)) {
            mkdir($this->basePath, recursive: true);
        }
    }

    public function put(string $key, mixed $content): void
    {
        $prefixedKey = $this->basePath . DIRECTORY_SEPARATOR . $key;

        file_put_contents($prefixedKey, $content);
    }

    public function get(string $key): ?string
    {
        $prefixedKey = $this->basePath . DIRECTORY_SEPARATOR . $key;

        if (!file_exists($prefixedKey)) {
            return null;
        }

        $content = @file_get_contents($prefixedKey);
        if ($content === false) {
            return null;
        }

        return $content;
    }
}
