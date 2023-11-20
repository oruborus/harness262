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

use function assert;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function json_encode;
use function mkdir;
use function serialize;
use function unserialize;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;

/**
 * @template TContent
 * @implements Storage<TContent>
 */
final readonly class SerializingFileStorage implements Storage
{
    public function __construct(
        private string $basePath
    ) {
        if (!file_exists($this->basePath)) {
            mkdir(directory: $this->basePath, recursive: true);
        }
    }

    /**
     * @param TContent $content
     */
    public function put(string $key, mixed $content): void
    {
        $prefixedKey = $this->basePath . DIRECTORY_SEPARATOR . $key;

        $serializedContent = serialize($content);

        $stringContent = json_encode($serializedContent, JSON_THROW_ON_ERROR);

        file_put_contents($prefixedKey, $stringContent);
    }

    /**
     * @return TContent
     */
    public function get(string $key): mixed
    {
        $prefixedKey = $this->basePath . DIRECTORY_SEPARATOR . $key;

        if (!file_exists($prefixedKey)) {
            return null;
        }

        $stringContent = @file_get_contents($prefixedKey);
        if ($stringContent === false) {
            return null;
        }

        $decodedContent = json_decode($stringContent, null, JSON_THROW_ON_ERROR);
        assert(is_string($decodedContent));
        /**
         * @var TContent
         */
        return unserialize($decodedContent);
    }
}
