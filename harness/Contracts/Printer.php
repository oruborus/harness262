<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface Printer
{
    public function setStepCount(int $stepCount): void;

    public function start(): void;

    public function step(TestResultState $state): void;

    /**
     * @var TestResult[] $testResults
     */
    public function end(array $testResults, int $duration): void;
}
