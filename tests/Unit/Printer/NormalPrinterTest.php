<?php

declare(strict_types=1);

namespace Tests\Unit\Printer;

use Generator;
use Oru\Harness\Contracts\Output;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Printer\NormalPrinter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stringable;

use const PHP_EOL;

#[CoversClass(NormalPrinter::class)]
final class NormalPrinterTest extends TestCase
{
    private function createOutput(): Output&Stringable
    {
        return new class implements Output, Stringable
        {
            private string $storage = '';

            public function write(string $content): void
            {
                $this->storage .= $content;
            }

            public function writeLn(string $content): void
            {
                $this->write($content . PHP_EOL);
            }

            public function __toString(): string
            {
                return $this->storage;
            }
        };
    }

    #[Test]
    public function printsSomethingOnStart(): void
    {
        $output = $this->createOutput();
        $exptectedOutput = $this->createOutput();
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('EcmaScript Test Harness');
        $exptectedOutput->writeLn('');
        $printer = new NormalPrinter($output);

        $printer->start();

        $this->assertSame((string) $exptectedOutput, (string) $output);
    }

    #[Test]
    #[DataProvider('provideStepMarker')]
    public function printsCorrectStepMarker(TestResultState $state, string $expected): void
    {
        $output = $this->createOutput();
        $printer = new NormalPrinter($output);

        $printer->step($state);

        $this->assertSame($expected, (string) $output);
    }

    /**
     * @return Generator<string, array{0:TestResultState, 1:string}
     */
    public static function provideStepMarker(): Generator
    {
        yield 'success' => [TestResultState::Success, '.'];
        yield 'fail'    => [TestResultState::Fail, 'F'];
        yield 'error'   => [TestResultState::Error, 'E'];
        yield 'cache'   => [TestResultState::Cache, '·'];
        yield 'skip'    => [TestResultState::Skip, 'S'];
    }

    #[Test]
    public function printsNewLineAfterStepWithoutCount(): void
    {
        $output = $this->createOutput();
        $exptectedOutput = $this->createOutput();
        $exptectedOutput->writeLn('............................................................... 63');
        $exptectedOutput->write('.....');
        $printer = new NormalPrinter($output);

        for ($i = 0; $i < NormalPrinter::STEPS_PER_LINE + 5; $i++) {
            $printer->step(TestResultState::Success);
        }

        $this->assertSame((string) $exptectedOutput, (string) $output);
    }

    #[Test]
    public function printsNewLineAfterStepWithCount(): void
    {
        $output = $this->createOutput();
        $exptectedOutput = $this->createOutput();
        $exptectedOutput->writeLn('............................................................... 63 / 68 ( 92%)');
        $exptectedOutput->write('.....');
        $printer = new NormalPrinter($output);
        $printer->setStepCount(68);

        for ($i = 0; $i < NormalPrinter::STEPS_PER_LINE + 5; $i++) {
            $printer->step(TestResultState::Success);
        }

        $this->assertSame((string) $exptectedOutput, (string) $output);
    }

    #[Test]
    #[DataProvider('provideDuration')]
    public function printsDurationOnEnd(int $duration, string $expected): void
    {
        $output = $this->createOutput();
        $exptectedOutput = $this->createOutput();
        $exptectedOutput->writeLn('');
        $exptectedOutput->write('Duration: ');
        $exptectedOutput->writeLn($expected);

        $printer = new NormalPrinter($output);
        $printer->end([], $duration);

        $this->assertSame((string) $exptectedOutput, (string) $output);
    }

    /**
     * @return Generator<string, array{0:int}
     */
    public static function provideDuration(): Generator
    {
        yield '00:00' => [0, '00:00'];
        yield '01:00' => [60, '01:00'];
        yield '01:00:00' => [3600, '01:00:00'];
    }

    #[Test]
    public function printsLastStepOnEnd(): void
    {
        $output = $this->createOutput();
        $exptectedOutput = $this->createOutput();
        $exptectedOutput->writeLn('.                                                               1 / 1 (100%)');
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('Duration: 00:00');

        $printer = new NormalPrinter($output);
        $printer->setStepCount(1);
        $printer->step(TestResultState::Success);
        $printer->end([], 0);

        $this->assertSame((string) $exptectedOutput, (string) $output);
    }

    #[Test]
    public function doesNotPrintLastStepOnEndIfLineWasFull(): void
    {
        $output = $this->createOutput();
        $exptectedOutput = $this->createOutput();
        $exptectedOutput->writeLn('............................................................... 63 / 63 (100%)');
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('Duration: 00:00');
        $printer = new NormalPrinter($output);
        $printer->setStepCount(63);

        for ($i = 0; $i < NormalPrinter::STEPS_PER_LINE; $i++) {
            $printer->step(TestResultState::Success);
        }
        $printer->end([], 0);

        $this->assertSame((string) $exptectedOutput, (string) $output);
    }

    #[Test]
    public function printsFailureListOnEnd(): void
    {
        $output = $this->createOutput();
        $exception1 = new RuntimeException();
        $exception2 = new RuntimeException();
        $exptectedOutput = $this->createOutput();

        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('Duration: 00:00');
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('There where failure(s)!');
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('FAILURES:');
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('1:');
        $exptectedOutput->writeLn((string) $exception1);
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('2:');
        $exptectedOutput->writeLn((string) $exception2);
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('');

        $printer = new NormalPrinter($output);
        $printer->end([
            $this->createConfiguredMock(TestResult::class, [
                'state' => TestResultState::Fail,
                'usedFiles' => [],
                'duration' => 0,
                'throwable' => $exception1
            ]),
            $this->createConfiguredMock(TestResult::class, [
                'state' => TestResultState::Fail,
                'usedFiles' => [],
                'duration' => 0,
                'throwable' => $exception2
            ])
        ], 0);

        $this->assertSame((string) $exptectedOutput, (string) $output);
    }

    #[Test]
    public function printsErrorListOnEnd(): void
    {
        $output = $this->createOutput();
        $error1 = new RuntimeException();
        $error2 = new RuntimeException();
        $exptectedOutput = $this->createOutput();

        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('Duration: 00:00');
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('There where error(s)!');
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('ERRORS:');
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('1:');
        $exptectedOutput->writeLn((string) $error1);
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('2:');
        $exptectedOutput->writeLn((string) $error2);
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('');

        $printer = new NormalPrinter($output);
        $printer->end([
            $this->createConfiguredMock(TestResult::class, [
                'state' => TestResultState::Error,
                'usedFiles' => [],
                'duration' => 0,
                'throwable' => $error1
            ]),
            $this->createConfiguredMock(TestResult::class, [
                'state' => TestResultState::Error,
                'usedFiles' => [],
                'duration' => 0,
                'throwable' => $error2
            ])
        ], 0);

        $this->assertSame((string) $exptectedOutput, (string) $output);
    }

    #[Test]
    public function printsFailureAndErrorListOnEnd(): void
    {
        $output = $this->createOutput();
        $exception1 = new RuntimeException();
        $exception2 = new RuntimeException();
        $error1 = new RuntimeException();
        $error2 = new RuntimeException();

        $exptectedOutput = $this->createOutput();
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('Duration: 00:00');
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('There where error(s) and failure(s)!');
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('FAILURES:');
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('1:');
        $exptectedOutput->writeLn((string) $exception1);
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('2:');
        $exptectedOutput->writeLn((string) $exception2);
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('ERRORS:');
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('1:');
        $exptectedOutput->writeLn((string) $error1);
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('2:');
        $exptectedOutput->writeLn((string) $error2);
        $exptectedOutput->writeLn('');
        $exptectedOutput->writeLn('');

        $printer = new NormalPrinter($output);
        $printer->end([
            $this->createConfiguredMock(TestResult::class, [
                'state' => TestResultState::Fail,
                'usedFiles' => [],
                'duration' => 0,
                'throwable' => $exception1
            ]),
            $this->createConfiguredMock(TestResult::class, [
                'state' => TestResultState::Error,
                'usedFiles' => [],
                'duration' => 0,
                'throwable' => $error1
            ]),
            $this->createConfiguredMock(TestResult::class, [
                'state' => TestResultState::Fail,
                'usedFiles' => [],
                'duration' => 0,
                'throwable' => $exception2
            ]),
            $this->createConfiguredMock(TestResult::class, [
                'state' => TestResultState::Error,
                'usedFiles' => [],
                'duration' => 0,
                'throwable' => $error2
            ])
        ], 0);

        $this->assertSame((string) $exptectedOutput, (string) $output);
    }
}
