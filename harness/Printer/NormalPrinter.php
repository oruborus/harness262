<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Printer;

use Oru\EcmaScript\Harness\Contracts\Output;
use Oru\EcmaScript\Harness\Contracts\Printer;
use Oru\EcmaScript\Harness\Contracts\TestResult;
use Oru\EcmaScript\Harness\Contracts\TestResultState;

use function array_filter;
use function count;
use function intdiv;
use function str_pad;
use function strlen;

use const STR_PAD_LEFT;

final class NormalPrinter implements Printer
{
    public const STEPS_PER_LINE = 63;

    private int $stepsPerformed = 0;

    private ?int $stepsPlanned = null;

    public function __construct(
        private Output $output
    ) {
    }

    public function setStepCount(int $stepCount): void
    {
        $this->stepsPlanned = $stepCount;
    }

    public function start(): void
    {
        $this->output->writeLn('');
        $this->output->writeLn('EcmaScript Test Harness');
        $this->output->writeLn('');
    }

    public function step(TestResultState $state): void
    {
        $this->stepsPerformed++;

        $short = match ($state) {
            TestResultState::Success => '.',
            TestResultState::Fail    => 'F',
            TestResultState::Error   => 'E',
            TestResultState::Cache   => '·',
            TestResultState::Skip    => 'S'
        };

        $this->output->write($short);

        if ($this->stepsPerformed % static::STEPS_PER_LINE > 0) {
            return;
        }

        if (is_null($this->stepsPlanned)) {
            $this->output->writeLn(" {$this->stepsPerformed}");
            return;
        }

        $stepsPerformedString = str_pad((string) $this->stepsPerformed, strlen((string) $this->stepsPlanned), ' ', STR_PAD_LEFT);
        $percentageString     = str_pad((string) intdiv($this->stepsPerformed * 100, $this->stepsPlanned), 3, ' ', STR_PAD_LEFT);

        $this->output->writeLn(" {$stepsPerformedString} / {$this->stepsPlanned} ({$percentageString}%)");
    }

    /**
     * @param TestResult[] $testResults
     */
    public function end(array $testResults, int $duration): void
    {
        $this->printLastStep();
        $this->printDuration($duration);

        $failures = array_filter($testResults, static fn (TestResult $r): bool => $r->state() === TestResultState::Fail);
        $errors   = array_filter($testResults, static fn (TestResult $r): bool => $r->state() === TestResultState::Error);

        if (count($failures) === 0 && count($errors) === 0) {
            return;
        }

        $this->output->writeLn('');
        if (count($failures) > 0 && count($errors) > 0) {
            $this->output->writeLn('There where error(s) and failure(s)!');
        } elseif (count($errors) > 0) {
            $this->output->writeLn('There where error(s)!');
        } else {
            $this->output->writeLn('There where failure(s)!');
        }
        $this->output->writeLn('');

        if (count($failures) > 0) {
            $this->output->writeLn('FAILURES:');
            $this->output->writeLn('');
            $this->printList($failures);
            $this->output->writeLn('');
        }

        if (count($errors) > 0) {
            $this->output->writeLn('ERRORS:');
            $this->output->writeLn('');
            $this->printList($errors);
            $this->output->writeLn('');
        }
    }

    private function printLastStep(): void
    {
        if ($this->stepsPerformed % static::STEPS_PER_LINE > 0) {
            $stepsPerformedString = str_pad((string) $this->stepsPerformed, strlen((string) $this->stepsPlanned), ' ', STR_PAD_LEFT);
            $percentageString     = str_pad((string) intdiv($this->stepsPerformed * 100, $this->stepsPlanned), 3, ' ', STR_PAD_LEFT);
            $fillString           = str_pad('', static::STEPS_PER_LINE - $this->stepsPerformed % static::STEPS_PER_LINE, ' ');

            $this->output->writeLn("{$fillString} {$stepsPerformedString} / {$this->stepsPlanned} ({$percentageString}%)");
            $this->output->writeLn('');
        }
    }

    private function printDuration(int $duration): void
    {
        $seconds = str_pad((string) ($duration % 60), 2, '0', STR_PAD_LEFT);
        $minutes = str_pad((string) (intdiv($duration, 60) % 60), 2, '0', STR_PAD_LEFT);
        $hours   = str_pad((string) intdiv($duration, 60 * 60), 2, '0', STR_PAD_LEFT);

        $this->output->write('Duration: ');
        if ($hours !== '00') {
            $this->output->write($hours);
            $this->output->write(':');
        }
        $this->output->write($minutes);
        $this->output->write(':');
        $this->output->write($seconds);
        $this->output->writeLn('');
    }

    /**
     * @param TestResult[] $testResults
     */
    private function printList(array $testResults): void
    {
        $count = 0;
        foreach ($testResults as $testResult) {
            $count++;
            $this->output->writeLn("{$count}:");
            $this->output->writeLn($testResult->throwable()->__toString());
            $this->output->writeLn('');
            $this->output->writeLn($testResult->throwable()->getTraceAsString());
            $this->output->writeLn('');
        }
    }
}
