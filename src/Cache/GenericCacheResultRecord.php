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

use Oru\Harness\Contracts\CacheResultRecord;
use Oru\Harness\Contracts\TestResult;

final readonly class GenericCacheResultRecord implements CacheResultRecord
{
    /**
     * @param array<string, string> $usedFiles
     */
    public function __construct(
        private string $hash,
        private array $usedFiles,
        private TestResult $result
    ) {}

    public function hash(): string
    {
        return $this->hash;
    }

    /**
     * @return array<string, string>
     */
    public function usedFiles(): array
    {
        return $this->usedFiles;
    }

    public function result(): TestResult
    {
        return $this->result;
    }
}
