<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface CacheResultRecord
{
    public function hash(): string;

    /**
     * @return array<string, string>
     */
    public function usedFiles(): array;

    public function result(): TestResult;
}
