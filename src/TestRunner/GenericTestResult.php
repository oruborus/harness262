<?php

declare(strict_types=1);

namespace Oru\Harness\TestRunner;

use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultState;
use Throwable;

final readonly class GenericTestResult implements TestResult
{
    /**
     * @param string[] $usedFiles
     */
    public function __construct(
        private TestResultState $state,
        private string $path,
        private array $usedFiles,
        private int $duration,
        private ?Throwable $throwable = null,
    ) {
    }

    public function state(): TestResultState
    {
        return $this->state;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return string[]
     */
    public function usedFiles(): array
    {
        return $this->usedFiles;
    }

    public function duration(): int
    {
        return $this->duration;
    }

    public function throwable(): ?Throwable
    {
        return $this->throwable;
    }
}
