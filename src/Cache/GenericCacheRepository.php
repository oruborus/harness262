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
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestResult;

use function is_null;

final readonly class GenericCacheRepository implements CacheRepository
{
    /**
     * @param Storage<CacheResultRecord> $storage
     * @param callable(TestCase):string $keyHashFunction
     * @param callable(string):string $fileHashFunction
     */
    public function __construct(
        private Storage $storage,
        private mixed $keyHashFunction,
        private mixed $fileHashFunction
    ) {}

    public function get(TestCase $testCase): ?TestResult
    {
        $key = $this->hashKey($testCase);

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

    public function set(TestCase $testCase, TestResult $result): void
    {
        $key = $this->hashKey($testCase);

        $usedFiles = [];
        foreach ($result->usedFiles() as $usedFile) {
            $usedFiles[$usedFile] = $this->hashFile($usedFile);
        }

        $this->storage->put($key, new GenericCacheResultRecord($key, $usedFiles, $result));
    }

    private function hashKey(TestCase $input): string
    {
        return ($this->keyHashFunction)($input);
    }

    private function hashFile(string $path): string
    {
        return ($this->fileHashFunction)($path);
    }
}
