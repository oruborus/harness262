<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

use Throwable;

interface TestResult
{
    public function state(): TestResultState;

    /**
     * @return string[]
     */
    public function usedFiles(): array;

    public function duration(): int;

    public function throwable(): ?Throwable;
}
