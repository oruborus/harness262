<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Printer;

use Oru\EcmaScript\Harness\Contracts\Printer;
use Oru\EcmaScript\Harness\Contracts\TestResultState;

final class SilentPrinter implements Printer
{
    public function setStepCount(int $stepCount): void
    {
    }

    public function start(): void
    {
    }

    public function step(TestResultState $state): void
    {
    }

    public function end(array $testResults, int $duration): void
    {
    }
}
