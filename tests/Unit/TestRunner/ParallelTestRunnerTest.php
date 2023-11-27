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

namespace Tests\Unit\TestRunner;

use ErrorException;
use Fiber;
use Oru\Harness\Config\GenericTestCase;
use Oru\Harness\Config\GenericTestSuiteConfig;
use Oru\Harness\Contracts\AssertionFactory;
use Oru\Harness\Contracts\Command;
use Oru\Harness\Contracts\ImplicitStrictness;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Frontmatter\GenericFrontmatter;
use Oru\Harness\TestRunner\GenericTestResult;
use Oru\Harness\TestRunner\ParallelTestRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use RuntimeException;

use function realpath;

#[CoversClass(ParallelTestRunner::class)]
#[UsesClass(GenericFrontmatter::class)]
#[UsesClass(GenericTestCase::class)]
#[UsesClass(GenericTestResult::class)]
final class ParallelTestRunnerTest extends PHPUnitTestCase
{
    #[Test]
    public function throwsWhenProcessOpeningFailed(): void
    {
        $this->expectExceptionObject(new RuntimeException('Could not open process'));

        $testRunner = new ParallelTestRunner(
            $this->createMock(AssertionFactory::class),
            $this->createMock(Printer::class),
            $this->createConfiguredMock(Command::class, ['__toString' => 'Ê↕'])
        );

        $testRunner->add($this->createMock(TestCase::class));
        $testRunner->run();
    }

    #[Test]
    public function fowardsExceptionThrownInTest(): void
    {
        $this->expectExceptionObject(new ErrorException('THROWN IN TEST'));

        $testRunner = new ParallelTestRunner(
            $this->createMock(AssertionFactory::class),
            $this->createMock(Printer::class),
            $this->createConfiguredMock(Command::class, ['__toString' => 'php ' . realpath('./tests/Utility/Template/FailingTestCase.php')])
        );

        $testRunner->add($this->createMock(TestCase::class));
        $testRunner->run();
    }

    #[Test]
    public function throwsWhenTestDosNotReturnTestResult(): void
    {
        $this->expectExceptionObject(new RuntimeException('Subprocess did not return a `TestResult` - Returned: O:8:"stdClass":1:{s:3:"AAA";s:3:"BBB";}'));

        $testRunner = new ParallelTestRunner(
            $this->createMock(AssertionFactory::class),
            $this->createMock(Printer::class),
            $this->createConfiguredMock(Command::class, ['__toString' => 'php ' . realpath('./tests/Utility/Template/NonTestResultReturningTestCase.php')])
        );

        $testRunner->add($this->createMock(TestCase::class));
        $testRunner->run();
    }

    #[Test]
    public function aggregatesTestResults(): void
    {
        $testRunner = new ParallelTestRunner(
            $this->createMock(AssertionFactory::class),
            $this->createMock(Printer::class),
            $this->createConfiguredMock(Command::class, ['__toString' => 'php ' . realpath('./tests/Utility/Template/SuccessfulTestCase.php')])
        );

        $testRunner->add($this->createMock(TestCase::class));
        $testRunner->add($this->createMock(TestCase::class));
        $actual = $testRunner->run();

        $this->assertContainsOnlyInstancesOf(TestResult::class, $actual);
        $this->assertCount(2, $actual);
    }

    #[Test]
    public function informsPrinterAboutStep(): void
    {
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->exactly(2))->method('step')->with(TestResultState::Success);

        $testRunner = new ParallelTestRunner(
            $this->createMock(AssertionFactory::class),
            $printerMock,
            $this->createConfiguredMock(Command::class, ['__toString' => 'php ' . realpath('./tests/Utility/Template/SuccessfulTestCase.php')])
        );

        $testRunner->add($this->createMock(TestCase::class));
        $testRunner->add($this->createMock(TestCase::class));
        $testRunner->run();
    }

    #[Test]
    public function deliversInputToSubprocess(): void
    {
        $this->expectExceptionObject(new RuntimeException('SUCCESS'));

        $testRunner = new ParallelTestRunner(
            $this->createMock(AssertionFactory::class),
            $this->createMock(Printer::class),
            $this->createConfiguredMock(Command::class, ['__toString' => 'php ' . realpath('./tests/Utility/Template/FailsOnMissingInputTestCase.php')])
        );

        $testCaseMock = new GenericTestCase(
            '',
            '',
            new GenericFrontmatter('description: x'),
            new GenericTestSuiteConfig([], false, 4, TestRunnerMode::Async, StopOnCharacteristic::Nothing),
            ImplicitStrictness::Unknown,
        );

        $testRunner->add($testCaseMock);
        $testRunner->run();
    }

    #[Test]
    public function willBeSuspendedWhenRunningInAFiber(): void
    {
        $testRunner = new ParallelTestRunner(
            $this->createMock(AssertionFactory::class),
            $this->createMock(Printer::class),
            $this->createConfiguredMock(Command::class, ['__toString' => 'php ' . realpath('./tests/Utility/Template/DelayedTestCase.php')])
        );

        $fiber = new Fiber(function () use ($testRunner) {
            $testRunner->add($this->createMock(TestCase::class));
            $testRunner->run();
        });
        $fiber->start();

        $count = 0;
        while ($fiber->isSuspended()) {
            $count++;
            $fiber->resume();
        }

        $actual = $testRunner->run();

        $this->assertGreaterThan(0, $count);
        $this->assertContainsOnlyInstancesOf(TestResult::class, $actual);
        $this->assertCount(1, $actual);
    }
}
