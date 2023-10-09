<?php

declare(strict_types=1);

namespace Oru\Harness\Test;

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
        private array $usedFiles,
        private int $duration,
        private ?Throwable $throwable = null,
    ) {
    }

    public function state(): TestResultState
    {
        return $this->state;
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
