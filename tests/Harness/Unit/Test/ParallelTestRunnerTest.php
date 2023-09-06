<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Test;

use ErrorException;
use Fiber;
use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\EcmaScript\Harness\Contracts\AssertionFactory;
use Oru\EcmaScript\Harness\Contracts\Command;
use Oru\EcmaScript\Harness\Contracts\Printer;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestResult;
use Oru\EcmaScript\Harness\Contracts\TestResultState;
use Oru\EcmaScript\Harness\Frontmatter\GenericFrontmatter;
use Oru\EcmaScript\Harness\Test\GenericTestConfig;
use Oru\EcmaScript\Harness\Test\GenericTestResult;
use Oru\EcmaScript\Harness\Test\ParallelTestRunner;
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
            $this->createMock(Engine::class),
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
            $this->createMock(Engine::class),
            $this->createMock(AssertionFactory::class),
            $this->createMock(Printer::class),
            $this->createConfiguredMock(Command::class, ['__toString' => 'php ' . realpath('./tests/Harness/Utility/Template/FailingTest.php')])
        );

        $testRunner->run($this->createMock(TestConfig::class));
    }

    #[Test]
    public function throwsWhenTestDosNotReturnTestResult(): void
    {
        $this->expectExceptionObject(new RuntimeException('Subprocess did not return a `TestResult` - Returned: O:8:"stdClass":1:{s:3:"AAA";s:3:"BBB";}'));

        $testRunner = new ParallelTestRunner(
            $this->createMock(Engine::class),
            $this->createMock(AssertionFactory::class),
            $this->createMock(Printer::class),
            $this->createConfiguredMock(Command::class, ['__toString' => 'php ' . realpath('./tests/Harness/Utility/Template/NonTestResultReturningTest.php')])
        );

        $testRunner->run($this->createMock(TestConfig::class));
    }

    #[Test]
    public function aggregatesTestResults(): void
    {
        $testRunner = new ParallelTestRunner(
            $this->createMock(Engine::class),
            $this->createMock(AssertionFactory::class),
            $this->createMock(Printer::class),
            $this->createConfiguredMock(Command::class, ['__toString' => 'php ' . realpath('./tests/Harness/Utility/Template/SuccessfulTest.php')])
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
            $this->createMock(Engine::class),
            $this->createMock(AssertionFactory::class),
            $printerMock,
            $this->createConfiguredMock(Command::class, ['__toString' => 'php ' . realpath('./tests/Harness/Utility/Template/SuccessfulTest.php')])
        );

        $testRunner->run($this->createMock(TestConfig::class));
        $testRunner->run($this->createMock(TestConfig::class));
    }

    #[Test]
    public function deliversInputToSubprocess(): void
    {
        $this->expectExceptionObject(new RuntimeException('SUCCESS'));

        $testRunner = new ParallelTestRunner(
            $this->createMock(Engine::class),
            $this->createMock(AssertionFactory::class),
            $this->createMock(Printer::class),
            $this->createConfiguredMock(Command::class, ['__toString' => 'php ' . realpath('./tests/Harness/Utility/Template/FailsOnMissingInputTest.php')])
        );

        $testConfigMock = new GenericTestConfig('', '', new GenericFrontmatter('description: x'));

        $testRunner->run($testConfigMock);
    }

    #[Test]
    public function willBeSupendedWhenRunningInAFiber(): void
    {
        $testRunner = new ParallelTestRunner(
            $this->createMock(Engine::class),
            $this->createMock(AssertionFactory::class),
            $this->createMock(Printer::class),
            $this->createConfiguredMock(Command::class, ['__toString' => 'php ' . realpath('./tests/Harness/Utility/Template/DelayedTest.php')])
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
