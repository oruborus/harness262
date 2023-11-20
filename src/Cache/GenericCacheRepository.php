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

namespace Oru\Harness\Cache;

use Oru\Harness\Contracts\CacheRepository;
use Oru\Harness\Contracts\CacheResultRecord;
use Oru\Harness\Contracts\Storage;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestResult;

use function hash_file;
use function is_null;
use function md5;
use function serialize;

final readonly class GenericCacheRepository implements CacheRepository
{
    /**
     * @param Storage<CacheResultRecord> $storage
     * @param callable(TestConfig):string $keyHashFunction
     * @param callable(string):string $fileHashFunction
     */
    public function __construct(
        private Storage $storage,
        private mixed $keyHashFunction,
        private mixed $fileHashFunction
    ) {}

    public function get(TestConfig $config): ?TestResult
    {
        $key = $this->hashKey($config);

        $content = $this->storage->get($key);
        if (is_null($content)) {
            return null;
        }

        foreach ($content->usedFiles() as $path => $hash) {
            if ($this->hashFile($path) !== $hash) {
                return null;
            }
        }


        return $content->result();
    }

    public function set(TestConfig $config, TestResult $result): void
    {
        $key = $this->hashKey($config);

        $usedFiles = [];
        foreach ($result->usedFiles() as $usedFile) {
            $usedFiles[$usedFile] = $this->hashFile($usedFile);
        }

        $this->storage->put($key, new GenericCacheResultRecord($key, $usedFiles, $result));
    }

    private function hashKey(TestConfig $input): string
    {
        return ($this->keyHashFunction)($input);
    }

    private function hashFile(string $path): string
    {
        return ($this->fileHashFunction)($path);
    }
}
