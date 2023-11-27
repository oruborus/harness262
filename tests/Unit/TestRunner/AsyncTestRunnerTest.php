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

use Closure;
use ErrorException;
use Generator;
use Oru\Harness\Config\GenericTestCase;
use Oru\Harness\Config\GenericTestSuite;
use Oru\Harness\Contracts\Command;
use Oru\Harness\Contracts\ImplicitStrictness;
use Oru\Harness\Contracts\Loop;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\Task;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Frontmatter\GenericFrontmatter;
use Oru\Harness\Loop\FiberTask;
use Oru\Harness\TestRunner\AsyncTestRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use RuntimeException;
use Tests\Utility\Loop\SimpleLoop;

#[CoversClass(AsyncTestRunner::class)]
#[UsesClass(FiberTask::class)]
final class AsyncTestRunnerTest extends PHPUnitTestCase
{
    #[Test]
    public function addsTaskToProvidedLoop(): void
    {
        $loopMock = $this->createMock(Loop::class);
        $loopMock->expects($this->once())->method('add');
        $printerStub = $this->createMock(Printer::class);
        $commandStub = $this->createMock(Command::class);
        $testCaseStub = $this->createMock(TestCase::class);

        $testRunner = new AsyncTestRunner($printerStub, $commandStub, $loopMock);
        $testRunner->add($testCaseStub);
    }

    #[Test]
    public function canRunASingleSucceedingTest(): void
    {
        $commandStub = $this->createConfiguredStub(Command::class, [
            '__toString' => 'php tests/Utility/Template/SuccessfulTestCase.php',
        ]);
        $testCaseStub = $this->createStub(TestCase::class);
        $testRunner = new AsyncTestRunner(
            $this->createStub(Printer::class),
            $commandStub,
            new SimpleLoop(),
        );

        $testRunner->add($testCaseStub);
        $actual = $testRunner->run();

        $this->assertCount(1, $actual);
        $this->assertSame($actual[0]->state(), TestResultState::Success);
    }

    #[Test]
    public function relaysThrowablesThrownInTestCase(): void
    {
        $this->expectExceptionObject(new ErrorException('THROWN IN TEST'));

        $commandStub = $this->createConfiguredStub(Command::class, [
            '__toString' => 'php tests/Utility/Template/FailingTestCase.php',
        ]);
        $testCaseStub = $this->createStub(TestCase::class);
        $testRunner = new AsyncTestRunner(
            $this->createStub(Printer::class),
            $commandStub,
            new SimpleLoop(),
        );

        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    public function throwsRuntimeExceptionWhenTestCaseDoesNotReturnAThrowableOrTestResult(): void
    {
        $output = serialize((object) ['AAA' => 'BBB']);
        $this->expectExceptionObject(new RuntimeException("Subprocess did not return a `TestResult` - Returned: {$output}"));

        $commandStub = $this->createConfiguredStub(Command::class, [
            '__toString' => 'php tests/Utility/Template/NonTestResultReturningTestCase.php',
        ]);
        $testCaseStub = $this->createStub(TestCase::class);
        $testRunner = new AsyncTestRunner(
            $this->createStub(Printer::class),
            $commandStub,
            new SimpleLoop(),
        );

        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    public function informsProvidedPrinterAboutCompletedTestCase(): void
    {
        $commandStub = $this->createConfiguredStub(Command::class, [
            '__toString' => 'php tests/Utility/Template/SuccessfulTestCase.php',
        ]);
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->once())->method('step');
        $testCaseStub = $this->createStub(TestCase::class);
        $testRunner = new AsyncTestRunner(
            $printerMock,
            $commandStub,
            new SimpleLoop(),
        );

        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    public function sendsDataToTestTemplate(): void
    {
        $this->expectExceptionObject(new RuntimeException('SUCCESS'));

        $commandStub = $this->createConfiguredStub(Command::class, [
            '__toString' => 'php tests/Utility/Template/FailsOnMissingInputTestCase.php',
        ]);
        $testCaseMock = new GenericTestCase(
            '',
            '',
            new GenericFrontmatter('description: x'),
            new GenericTestSuite([], false, 4, TestRunnerMode::Async, StopOnCharacteristic::Nothing),
            ImplicitStrictness::Unknown,
        );
        $testRunner = new AsyncTestRunner(
            $this->createMock(Printer::class),
            $commandStub,
            new SimpleLoop(),
        );

        $testRunner->add($testCaseMock);
        $testRunner->run();
    }

    #[Test]
    public function resumesFromLongRunningTestCase(): void
    {
        $commandStub = $this->createConfiguredStub(Command::class, [
            '__toString' => 'php tests/Utility/Template/DelayedTestCase.php',
        ]);
        $testCaseStub = $this->createStub(TestCase::class);
        $testRunner = new AsyncTestRunner(
            $this->createMock(Printer::class),
            $commandStub,
            new class ($this->assertFalse(...)) implements Loop {
                public function __construct(
                    private Closure $assertFalse
                ) {}

                /**
                 * @var Task[] $tasks
                 */
                private array $tasks = [];

                public function add(Task $task): void
                {
                    $this->tasks[] = $task;
                }

                public function run(): void
                {
                    foreach ($this->tasks as $task) {
                        $task->continue();
                        ($this->assertFalse)($task->done());
                    }
                }
            }
        );

        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    #[DataProvider('provideContents')]
    public function stopsExecutionWhenStopOnCharacteristicIsMet(StopOnCharacteristic $stopOnCharacteristic, array $contents, int $expectedCount): void
    {
        $commandStub = $this->createConfiguredStub(Command::class, [
            '__toString' => 'php tests/Utility/Template/BasedOnContentTestCase.php',
        ]);
        $testCases = array_map(
            static fn(string $content): TestCase => new GenericTestCase(
                '',
                $content,
                new GenericFrontmatter('description: x'),
                new GenericTestSuite([], false, 4, TestRunnerMode::Async, $stopOnCharacteristic),
                ImplicitStrictness::Unknown,
            ),
            $contents
        );
        $testRunner = new AsyncTestRunner(
            $this->createMock(Printer::class),
            $commandStub,
            new SimpleLoop(),
        );
        foreach ($testCases as $testCase) {
            $testRunner->add($testCase);
        }

        $actual = $testRunner->run();

        $this->assertCount($expectedCount, $actual);
    }

    public static function provideContents(): Generator
    {
        yield 'nothing'  => [StopOnCharacteristic::Nothing, ['success', 'success', 'failure', 'error', 'success', 'success'], 6];
        yield 'error'    => [StopOnCharacteristic::Error, ['success', 'success', 'failure', 'error', 'success', 'success'], 4];
        yield 'failure'  => [StopOnCharacteristic::Failure, ['success', 'success', 'failure', 'error', 'success', 'success'], 3];
        yield 'defect 1' => [StopOnCharacteristic::Defect, ['success', 'success', 'failure', 'error', 'success', 'success'], 3];
        yield 'defect 2' => [StopOnCharacteristic::Defect, ['success', 'success', 'error', 'failure', 'success', 'success'], 3];
    }
}
