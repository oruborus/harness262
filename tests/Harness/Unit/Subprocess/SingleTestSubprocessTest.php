<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Subprocess;

use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestResult;
use Oru\EcmaScript\Harness\Contracts\TestRunner;
use Oru\EcmaScript\Harness\Subprocess\Exception\InvalidReturnValueException;
use Oru\EcmaScript\Harness\Subprocess\SingleTestSubprocess;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SingleTestSubprocess::class)]
final class SingleTestSubprocessTest extends TestCase
{
    #[Test]
    public function forwardsTheSingleTestResultFromTheProvidedTestRunner(): void
    {
        $expected = $this->createMock(TestResult::class);
        $testRunnerMock = $this->createMock(TestRunner::class);
        $testRunnerMock->expects($this->once())->method('run');
        $testRunnerMock->method('finalize')->willReturn([$expected]);
        $testConfigMock = $this->createMock(TestConfig::class);

        $subprocess = new SingleTestSubprocess($testRunnerMock, $testConfigMock);
        $actual = $subprocess->run();

        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function failsWhenProvidedTestRunnerReturnsMoreThanOneTestResult(): void
    {
        $this->expectExceptionObject(new InvalidReturnValueException('Test runner returned more than one test result'));

        $testRunnerMock = $this->createMock(TestRunner::class);
        $testRunnerMock->method('finalize')->willReturn([$this->createMock(TestResult::class), $this->createMock(TestResult::class)]);
        $testConfigMock = $this->createMock(TestConfig::class);

        (new SingleTestSubprocess($testRunnerMock, $testConfigMock))->run();
    }

    #[Test]
    public function failsWhenProvidedTestRunnerReturnsNoTestResult(): void
    {
        $this->expectExceptionObject(new InvalidReturnValueException('Test runner returned no test result'));

        $testRunnerMock = $this->createMock(TestRunner::class);
        $testRunnerMock->method('finalize')->willReturn([]);
        $testConfigMock = $this->createMock(TestConfig::class);

        (new SingleTestSubprocess($testRunnerMock, $testConfigMock))->run();
    }
}
