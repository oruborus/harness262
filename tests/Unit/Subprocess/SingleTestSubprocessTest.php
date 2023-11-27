<?php

/**
 * Copyright (c) 2023, Felix Jahn
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

use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestRunner;
use Oru\Harness\Subprocess\Exception\InvalidReturnValueException;
use Oru\Harness\Subprocess\SingleTestSubprocess;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

#[CoversClass(SingleTestSubprocess::class)]
final class SingleTestSubprocessTest extends PHPUnitTestCase
{
    #[Test]
    public function forwardsTheSingleTestResultFromTheProvidedTestRunner(): void
    {
        $expected = $this->createMock(TestResult::class);
        $testRunnerMock = $this->createMock(TestRunner::class);
        $testRunnerMock->expects($this->once())->method('add');
        $testRunnerMock->method('run')->willReturn([$expected]);
        $testCaseMock = $this->createMock(TestCase::class);

        $subprocess = new SingleTestSubprocess($testRunnerMock, $testCaseMock);
        $actual = $subprocess->run();

        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function failsWhenProvidedTestRunnerReturnsMoreThanOneTestResult(): void
    {
        $this->expectExceptionObject(new InvalidReturnValueException('Test runner returned more than one test result'));

        $testRunnerMock = $this->createMock(TestRunner::class);
        $testRunnerMock->method('run')->willReturn([$this->createMock(TestResult::class), $this->createMock(TestResult::class)]);
        $testCaseMock = $this->createMock(TestCase::class);

        (new SingleTestSubprocess($testRunnerMock, $testCaseMock))->run();
    }

    #[Test]
    public function failsWhenProvidedTestRunnerReturnsNoTestResult(): void
    {
        $this->expectExceptionObject(new InvalidReturnValueException('Test runner returned no test result'));

        $testRunnerMock = $this->createMock(TestRunner::class);
        $testRunnerMock->method('run')->willReturn([]);
        $testCaseMock = $this->createMock(TestCase::class);

        (new SingleTestSubprocess($testRunnerMock, $testCaseMock))->run();
    }
}
