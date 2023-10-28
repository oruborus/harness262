<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface Printer
{
    public function setStepCount(int $stepCount): void;

    public function writeLn(string $line): void;

    public function start(): void;

    public function step(TestResultState $state): void;

    /**
     * @param TestResult[] $testResults
     */
    public function end(array $testResults, int $duration): void;
}
