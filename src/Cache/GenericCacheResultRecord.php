<?php

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
