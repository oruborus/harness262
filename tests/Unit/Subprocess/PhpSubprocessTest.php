<?php

/**
 * Copyright (c) 2024, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Tests\Unit\Subprocess;

use ErrorException;
use Fiber;
use Generator;
use Oru\Harness\Contracts\Subprocess;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultFactory;
use Oru\Harness\Contracts\TestSuite;
use Oru\Harness\Subprocess\Exception\InvalidReturnValueException;
use Oru\Harness\Subprocess\Exception\ProcessAlreadyRunningException;
use Oru\Harness\Subprocess\Exception\ProcessFailureException;
use Oru\Harness\Subprocess\PhpSubprocess;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\PhpSubprocess as SymfonyPhpSubprocess;
use Tests\Unit\CanCreateTestResultFactoryStub;
use Throwable;

use function serialize;

#[CoversClass(PhpSubprocess::class)]
final class PhpSubprocessTest extends PHPUnitTestCase
{
    use CanCreateTestResultFactoryStub;

    private function createSubprocess(
        ?SymfonyPhpSubprocess $phpSubprocess = null,
        ?TestCase $testCase = null,
        ?TestResultFactory $testResultFactory = null,
    ): Subprocess {
        return new PhpSubprocess(
            $phpSubprocess ?? $this->createStub(SymfonyPhpSubprocess::class),
            $testCase ?? $this->createStub(TestCase::class),
            $testResultFactory ?? $this->createTestResultFactoryStub(),
        );
    }

    #[Test]
    public function configuresTheProvidedSubprocessCorrectly(): void
    {
        $expectedTimeout = 123;
        $testCaseStub = $this->createConfiguredStub(TestCase::class, [
            'testSuite' => $this->createConfiguredStub(TestSuite::class, [
                'timeout' => $expectedTimeout,
            ]),
        ]);
        $phpSubprocessMock = $this->createConfiguredMock(SymfonyPhpSubprocess::class, [
            'getExitCode' => 0,
            'getOutput' => serialize($this->createStub(TestResult::class)),
        ]);
        $phpSubprocessMock->expects($this->once())->method('setInput')->with(serialize($testCaseStub));
        $phpSubprocessMock->expects($this->once())->method('setTimeout')->with($expectedTimeout);

        $subprocess = $this->createSubprocess(
            phpSubprocess: $phpSubprocessMock,
            testCase: $testCaseStub,
        );

        $subprocess->run();
    }

    #[Test]
    #[DataProvider('provideSubprocessStartException')]
    /** @param class-string<Throwable> $expected */
    public function failsWhenTheProcessCannotStart(string $expected, Throwable $exception): void
    {
        $this->expectException($expected);

        $phpSubprocessMock = $this->createMock(SymfonyPhpSubprocess::class);
        $phpSubprocessMock->expects($this->once())->method('start')
            ->willThrowException($exception);
        $subprocess = $this->createSubprocess(
            phpSubprocess: $phpSubprocessMock,
        );

        $subprocess->run();
    }

    public static function provideSubprocessStartException(): Generator
    {
        yield 'process failing to start' => [ProcessFailureException::class, static::createStub(ProcessStartFailedException::class)];
        yield 'process already started'  => [ProcessAlreadyRunningException::class, static::createStub(RuntimeException::class)];
    }

    #[Test]
    public function processGetsSuspendedWhenRunningAndNotTimedOut(): void
    {
        $subprocess = $this->createSubprocess(
            phpSubprocess: $this->createConfiguredStub(SymfonyPhpSubprocess::class, [
                'getExitCode' => 0,
                'getOutput' => serialize($this->createStub(TestResult::class)),
                'isRunning' => true,
            ]),
        );

        $fiber = new Fiber(function () use ($subprocess) {
            $subprocess->run();
            $this->fail('Function did not suspend the Fiber.');
        });

        $fiber->start();

        $this->assertTrue($fiber->isSuspended(), 'Fiber was not suspended.');
    }

    #[Test]
    #[DataProvider('provideProcessStatus')]
    public function processGetsNotSuspendedWhenNotRunningOrTimedOut(bool $running, bool $timedOut): void
    {
        $phpSubprocessStub = $this->createConfiguredStub(SymfonyPhpSubprocess::class, [
            'getExitCode' => 0,
            'getOutput' => serialize($this->createStub(TestResult::class)),
            'isRunning' => $running,
        ]);
        if ($timedOut) {
            $phpSubprocessStub->method('checkTimeout')
                ->willThrowException($this->createStub(ProcessTimedOutException::class));
        }
        $subprocess = $this->createSubprocess(
            phpSubprocess: $phpSubprocessStub,
        );

        $fiber = new Fiber(function () use ($subprocess) {
            $subprocess->run();
        });

        $fiber->start();

        $this->assertTrue($fiber->isTerminated(), 'Fiber was suspended.');
    }

    public static function provideProcessStatus(): Generator
    {
        yield 'process not running' => [false, false];
        yield 'process timed out'  => [true, true];
    }

    #[Test]
    public function failsWhenProcessResultCouldNotBeDeserialized(): void
    {
        $this->expectException(InvalidReturnValueException::class);

        $subprocess = $this->createSubprocess(
            phpSubprocess: $this->createConfiguredStub(SymfonyPhpSubprocess::class, [
                'getExitCode' => 0,
                'getOutput' => "NOT A SERIALIZED TEST RESULT",
            ]),
        );

        $subprocess->run();
    }

    #[Test]
    public function rethrowsTheOccurringThrowables(): void
    {
        $this->expectException(ErrorException::class);

        $subprocess = $this->createSubprocess(
            phpSubprocess: $this->createConfiguredStub(SymfonyPhpSubprocess::class, [
                'getExitCode' => 0,
                'getOutput' => serialize($this->createStub(ErrorException::class)),
            ]),
        );

        $subprocess->run();
    }

    #[Test]
    public function failsWhenProcessDoesNotReturnTestResult(): void
    {
        $this->expectException(InvalidReturnValueException::class);

        $subprocess = $this->createSubprocess(
            phpSubprocess: $this->createConfiguredStub(SymfonyPhpSubprocess::class, [
                'getExitCode' => 0,
                'getOutput' => serialize("NOT A TEST RESULT"),
            ]),
        );

        $subprocess->run();
    }

    #[Test]
    public function returnsTestResultOnSuccess(): void
    {
        $subprocess = $this->createSubprocess(
            phpSubprocess: $this->createConfiguredStub(SymfonyPhpSubprocess::class, [
                'getExitCode' => 0,
                'getOutput' => serialize($this->createStub(TestResult::class)),
            ]),
        );

        $actual = $subprocess->run();

        $this->assertInstanceOf(TestResult::class, $actual);
    }
}
