<?php

declare(strict_types=1);

namespace Oru\Harness\Printer;

use Oru\Harness\Contracts\Output;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultState;

use function assert;
use function array_filter;
use function count;
use function date;
use function intdiv;
use function is_null;
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

    public function writeLn(string $line): void
    {
        $this->output->writeLn($line);
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

        $this->output->writeLn($this->getStatusLineEnd());
    }

    private function getStatusLineEnd(): string
    {
        assert(!is_null($this->stepsPlanned));

        $stepsPerformedString = str_pad((string) $this->stepsPerformed, strlen((string) $this->stepsPlanned), ' ', STR_PAD_LEFT);
        $percentageString     = str_pad((string) intdiv($this->stepsPerformed * 100, $this->stepsPlanned), 3, ' ', STR_PAD_LEFT);

        return " {$stepsPerformedString} / {$this->stepsPlanned} ({$percentageString}%)";
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
        } elseif (count($failures) > 0) {
            $this->output->writeLn('There where failure(s)!');
        } else {
            $this->output->writeLn('There where error(s)!');
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
        if ($this->stepsPerformed % static::STEPS_PER_LINE === 0) {
            $this->output->writeLn('');
            return;
        }

        $fillString = str_pad('', static::STEPS_PER_LINE - $this->stepsPerformed % static::STEPS_PER_LINE, ' ');

        $this->output->writeLn("{$fillString}{$this->getStatusLineEnd()}");
        $this->output->writeLn('');
    }

    private function printDuration(int $duration): void
    {
        $format = $duration >= 3600 ? 'H:i:s' : 'i:s';

        $this->output->write('Duration: ');
        $this->output->write(date($format, $duration));
        $this->output->writeLn('');
    }

    /**
     * @param TestResult[] $testResults
     */
    private function printList(array $testResults): void
    {
        $count = 0;
        foreach ($testResults as $testResult) {
            $throwable = $testResult->throwable();
            assert(!is_null($throwable));
            $count++;
            $this->output->writeLn("{$count}: {$testResult->path()}");
            $this->output->writeLn($throwable->__toString());
            $this->output->writeLn('');
        }
    }
}
