<?php

declare(strict_types=1);

namespace Tests\Unit\TestRunner;

use Closure;
use ErrorException;
use Oru\Harness\Config\GenericTestConfig;
use Oru\Harness\Config\GenericTestSuiteConfig;
use Oru\Harness\Contracts\Command;
use Oru\Harness\Contracts\Loop;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\Task;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Frontmatter\GenericFrontmatter;
use Oru\Harness\Loop\FiberTask;
use Oru\Harness\TestRunner\AsyncTestRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Utility\Loop\SimpleLoop;

#[CoversClass(AsyncTestRunner::class)]
#[UsesClass(FiberTask::class)]
final class AsyncTestRunnerTest extends TestCase
{
    #[Test]
    public function addsTaskToProvidedLoop(): void
    {
        $loopMock = $this->createMock(Loop::class);
        $loopMock->expects($this->once())->method('add');
        $printerStub = $this->createMock(Printer::class);
        $commandStub = $this->createMock(Command::class);
        $testConfigStub = $this->createMock(TestConfig::class);

        $testRunner = new AsyncTestRunner($printerStub, $commandStub, $loopMock);
        $testRunner->add($testConfigStub);
    }

    #[Test]
    public function canRunASingleSucceedingTest(): void
    {
        $commandStub = $this->createConfiguredStub(Command::class, [
            '__toString' => 'php tests/Utility/Template/SuccessfulTestCase.php',
        ]);
        $testConfigStub = $this->createStub(TestConfig::class);
        $testRunner = new AsyncTestRunner(
            $this->createStub(Printer::class),
            $commandStub,
            new SimpleLoop(),
        );

        $testRunner->add($testConfigStub);
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
        $testConfigStub = $this->createStub(TestConfig::class);
        $testRunner = new AsyncTestRunner(
            $this->createStub(Printer::class),
            $commandStub,
            new SimpleLoop(),
        );

        $testRunner->add($testConfigStub);
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
        $testConfigStub = $this->createStub(TestConfig::class);
        $testRunner = new AsyncTestRunner(
            $this->createStub(Printer::class),
            $commandStub,
            new SimpleLoop(),
        );

        $testRunner->add($testConfigStub);
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
        $testConfigStub = $this->createStub(TestConfig::class);
        $testRunner = new AsyncTestRunner(
            $printerMock,
            $commandStub,
            new SimpleLoop(),
        );

        $testRunner->add($testConfigStub);
        $testRunner->run();
    }

    #[Test]
    public function sendsDataToTestTemplate(): void
    {
        $this->expectExceptionObject(new RuntimeException('SUCCESS'));

        $commandStub = $this->createConfiguredStub(Command::class, [
            '__toString' => 'php tests/Utility/Template/FailsOnMissingInputTestCase.php',
        ]);
        $testConfigMock = new GenericTestConfig(
            '',
            '',
            new GenericFrontmatter('description: x'),
            new GenericTestSuiteConfig([], false, 4, TestRunnerMode::Async, StopOnCharacteristic::Nothing)
        );
        $testRunner = new AsyncTestRunner(
            $this->createMock(Printer::class),
            $commandStub,
            new SimpleLoop(),
        );

        $testRunner->add($testConfigMock);
        $testRunner->run();
    }

    #[Test]
    public function resumesFromLongRunningTestCase(): void
    {
        $commandStub = $this->createConfiguredStub(Command::class, [
            '__toString' => 'php tests/Utility/Template/DelayedTestCase.php',
        ]);
        $testConfigStub = $this->createStub(TestConfig::class);
        $testRunner = new AsyncTestRunner(
            $this->createMock(Printer::class),
            $commandStub,
            new class($this->assertFalse(...)) implements Loop
            {
                public function __construct(
                    private Closure $assertFalse
                ) {
                }

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

        $testRunner->add($testConfigStub);
        $testRunner->run();
    }
}
