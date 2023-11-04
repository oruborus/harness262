<?php

declare(strict_types=1);

namespace Tests\Unit\TestRunner;

use ErrorException;
use Fiber;
use Oru\Harness\Config\GenericTestConfig;
use Oru\Harness\Config\GenericTestSuiteConfig;
use Oru\Harness\Contracts\AssertionFactory;
use Oru\Harness\Contracts\Command;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Frontmatter\GenericFrontmatter;
use Oru\Harness\TestRunner\GenericTestResult;
use Oru\Harness\TestRunner\ParallelTestRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function realpath;

#[CoversClass(ParallelTestRunner::class)]
#[UsesClass(GenericFrontmatter::class)]
#[UsesClass(GenericTestConfig::class)]
#[UsesClass(GenericTestResult::class)]
final class ParallelTestRunnerTest extends TestCase
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

        $testRunner->run($this->createMock(TestConfig::class));
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

        $testRunner->run($this->createMock(TestConfig::class));
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

        $testRunner->run($this->createMock(TestConfig::class));
    }

    #[Test]
    public function aggregatesTestResults(): void
    {
        $testRunner = new ParallelTestRunner(
            $this->createMock(AssertionFactory::class),
            $this->createMock(Printer::class),
            $this->createConfiguredMock(Command::class, ['__toString' => 'php ' . realpath('./tests/Utility/Template/SuccessfulTestCase.php')])
        );

        $testRunner->run($this->createMock(TestConfig::class));
        $testRunner->run($this->createMock(TestConfig::class));
        $actual = $testRunner->finalize();

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

        $testRunner->run($this->createMock(TestConfig::class));
        $testRunner->run($this->createMock(TestConfig::class));
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

        $testConfigMock = new GenericTestConfig(
            '',
            '',
            new GenericFrontmatter('description: x'),
            new GenericTestSuiteConfig([], false, 4, TestRunnerMode::Async, StopOnCharacteristic::Nothing)
        );

        $testRunner->run($testConfigMock);
    }

    #[Test]
    public function willBeSupendedWhenRunningInAFiber(): void
    {
        $testRunner = new ParallelTestRunner(
            $this->createMock(AssertionFactory::class),
            $this->createMock(Printer::class),
            $this->createConfiguredMock(Command::class, ['__toString' => 'php ' . realpath('./tests/Utility/Template/DelayedTestCase.php')])
        );

        $fiber = new Fiber(fn () => $testRunner->run($this->createMock(TestConfig::class)));
        $fiber->start();

        $count = 0;
        while ($fiber->isSuspended()) {
            $count++;
            $this->assertSame([], $testRunner->finalize());
            $fiber->resume();
        }

        $actual = $testRunner->finalize();

        $this->assertGreaterThan(0, $count);
        $this->assertContainsOnlyInstancesOf(TestResult::class, $actual);
        $this->assertCount(1, $actual);
    }
}
