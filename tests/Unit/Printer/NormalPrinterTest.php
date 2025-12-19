<?php

/**
 * Copyright (c) 2023-2024, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

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
        return new class() implements Output, Stringable
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
    public function printsSingleLineToProvidedOutput(): void
    {
        $expected = 'Expected output';
        $output = $this->createOutput();
        $expectedOutput = $this->createOutput();
        $expectedOutput->writeLn($expected);
        $printer = new NormalPrinter($output);

        $printer->writeLn($expected);

        $this->assertSame((string) $expectedOutput, (string) $output);
    }

    #[Test]
    public function printsNewLineToProvidedOutput(): void
    {
        $output = $this->createOutput();
        $expectedOutput = $this->createOutput();
        $expectedOutput->writeLn('');
        $printer = new NormalPrinter($output);

        $printer->newLine();

        $this->assertSame((string) $expectedOutput, (string) $output);
    }

    #[Test]
    public function printsSomethingOnStart(): void
    {
        $output = $this->createOutput();
        $expectedOutput = $this->createOutput();
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('EcmaScript Test Harness');
        $expectedOutput->writeLn('');
        $printer = new NormalPrinter($output);

        $printer->start();

        $this->assertSame((string) $expectedOutput, (string) $output);
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
        yield 'timeout' => [TestResultState::Timeout, 'T'];
    }

    #[Test]
    public function printsNewLineAfterStepWithoutCount(): void
    {
        $output = $this->createOutput();
        $expectedOutput = $this->createOutput();
        $expectedOutput->writeLn('............................................................... 63');
        $expectedOutput->write('.....');
        $printer = new NormalPrinter($output);

        for ($i = 0; $i < NormalPrinter::STEPS_PER_LINE + 5; $i++) {
            $printer->step(TestResultState::Success);
        }

        $this->assertSame((string) $expectedOutput, (string) $output);
    }

    #[Test]
    public function printsNewLineAfterStepWithCount(): void
    {
        $output = $this->createOutput();
        $expectedOutput = $this->createOutput();
        $expectedOutput->writeLn('............................................................... 63 / 68 ( 92%)');
        $expectedOutput->write('.....');
        $printer = new NormalPrinter($output);
        $printer->setStepCount(68);

        for ($i = 0; $i < NormalPrinter::STEPS_PER_LINE + 5; $i++) {
            $printer->step(TestResultState::Success);
        }

        $this->assertSame((string) $expectedOutput, (string) $output);
    }

    #[Test]
    #[DataProvider('provideDuration')]
    public function printsDurationOnEnd(int $duration, string $expected): void
    {
        $output = $this->createOutput();
        $expectedOutput = $this->createOutput();
        $expectedOutput->writeLn('');
        $expectedOutput->write('Duration: ');
        $expectedOutput->writeLn($expected);

        $printer = new NormalPrinter($output);
        $printer->end([], $duration);

        $this->assertSame((string) $expectedOutput, (string) $output);
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
        $expectedOutput = $this->createOutput();
        $expectedOutput->writeLn('.                                                               1 / 1 (100%)');
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('Duration: 00:00');

        $printer = new NormalPrinter($output);
        $printer->setStepCount(1);
        $printer->step(TestResultState::Success);
        $printer->end([], 0);

        $this->assertSame((string) $expectedOutput, (string) $output);
    }

    #[Test]
    public function doesNotPrintLastStepOnEndIfLineWasFull(): void
    {
        $output = $this->createOutput();
        $expectedOutput = $this->createOutput();
        $expectedOutput->writeLn('............................................................... 63 / 63 (100%)');
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('Duration: 00:00');
        $printer = new NormalPrinter($output);
        $printer->setStepCount(63);

        for ($i = 0; $i < NormalPrinter::STEPS_PER_LINE; $i++) {
            $printer->step(TestResultState::Success);
        }
        $printer->end([], 0);

        $this->assertSame((string) $expectedOutput, (string) $output);
    }

    #[Test]
    public function printsFailureListOnEnd(): void
    {
        $output = $this->createOutput();
        $exception1 = new RuntimeException();
        $exception2 = new RuntimeException();
        $expectedOutput = $this->createOutput();

        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('Duration: 00:00');
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('There where failure(s)!');
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('FAILURES:');
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('1: path1');
        $expectedOutput->writeLn((string) $exception1);
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('2: path2');
        $expectedOutput->writeLn((string) $exception2);
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('');

        $printer = new NormalPrinter($output);
        $printer->end([
            $this->createConfiguredStub(TestResult::class, [
                'state' => TestResultState::Fail,
                'path' => 'path1',
                'usedFiles' => [],
                'duration' => 0,
                'throwable' => $exception1
            ]),
            $this->createConfiguredStub(TestResult::class, [
                'state' => TestResultState::Fail,
                'path' => 'path2',
                'usedFiles' => [],
                'duration' => 0,
                'throwable' => $exception2
            ])
        ], 0);

        $this->assertSame((string) $expectedOutput, (string) $output);
    }

    #[Test]
    public function printsErrorListOnEnd(): void
    {
        $output = $this->createOutput();
        $error1 = new RuntimeException();
        $error2 = new RuntimeException();
        $expectedOutput = $this->createOutput();

        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('Duration: 00:00');
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('There where error(s)!');
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('ERRORS:');
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('1: path1');
        $expectedOutput->writeLn((string) $error1);
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('2: path2');
        $expectedOutput->writeLn((string) $error2);
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('');

        $printer = new NormalPrinter($output);
        $printer->end([
            $this->createConfiguredStub(TestResult::class, [
                'state' => TestResultState::Error,
                'path' => 'path1',
                'usedFiles' => [],
                'duration' => 0,
                'throwable' => $error1
            ]),
            $this->createConfiguredStub(TestResult::class, [
                'state' => TestResultState::Error,
                'path' => 'path2',
                'usedFiles' => [],
                'duration' => 0,
                'throwable' => $error2
            ])
        ], 0);

        $this->assertSame((string) $expectedOutput, (string) $output);
    }

    #[Test]
    public function printsFailureAndErrorListOnEnd(): void
    {
        $output = $this->createOutput();
        $exception1 = new RuntimeException();
        $exception2 = new RuntimeException();
        $error1 = new RuntimeException();
        $error2 = new RuntimeException();

        $expectedOutput = $this->createOutput();
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('Duration: 00:00');
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('There where error(s) and failure(s)!');
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('FAILURES:');
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('1: path1');
        $expectedOutput->writeLn((string) $exception1);
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('2: path2');
        $expectedOutput->writeLn((string) $exception2);
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('ERRORS:');
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('1: path3');
        $expectedOutput->writeLn((string) $error1);
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('2: path4');
        $expectedOutput->writeLn((string) $error2);
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('');

        $printer = new NormalPrinter($output);
        $printer->end([
            $this->createConfiguredStub(TestResult::class, [
                'state' => TestResultState::Fail,
                'path' => 'path1',
                'usedFiles' => [],
                'duration' => 0,
                'throwable' => $exception1
            ]),
            $this->createConfiguredStub(TestResult::class, [
                'state' => TestResultState::Error,
                'path' => 'path3',
                'usedFiles' => [],
                'duration' => 0,
                'throwable' => $error1
            ]),
            $this->createConfiguredStub(TestResult::class, [
                'state' => TestResultState::Fail,
                'path' => 'path2',
                'usedFiles' => [],
                'duration' => 0,
                'throwable' => $exception2
            ]),
            $this->createConfiguredStub(TestResult::class, [
                'state' => TestResultState::Error,
                'path' => 'path4',
                'usedFiles' => [],
                'duration' => 0,
                'throwable' => $error2
            ])
        ], 0);

        $this->assertSame((string) $expectedOutput, (string) $output);
    }

    #[Test]
    public function printsTimeoutsListOnEnd(): void
    {
        $output = $this->createOutput();
        $expectedOutput = $this->createOutput();

        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('Duration: 00:00');
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('TIMEOUTS:');
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('1: path1');
        $expectedOutput->writeLn('');
        $expectedOutput->writeLn('');

        $printer = new NormalPrinter($output);
        $printer->end([
            $this->createConfiguredStub(TestResult::class, [
                'state' => TestResultState::Timeout,
                'path' => 'path1',
                'usedFiles' => [],
                'duration' => 0,
            ]),
        ], 0);

        $this->assertSame((string) $expectedOutput, (string) $output);
    }
}
