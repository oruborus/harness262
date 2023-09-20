<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Test;

use Oru\EcmaScript\Harness\Contracts\Loop;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestResult;
use Oru\EcmaScript\Harness\Contracts\TestRunner;
use Oru\EcmaScript\Harness\Loop\FiberTask;
use Oru\EcmaScript\Harness\Test\AsyncTestRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Tests\Harness\Utility\Loop\SimpleLoop;

#[CoversClass(AsyncTestRunner::class)]
#[UsesClass(FiberTask::class)]
final class AsyncTestRunnerTest extends TestCase
{
    #[Test]
    public function addsTaskToProvidedLoop(): void
    {
        $testRunnerMock = $this->createMock(TestRunner::class);
        $loopMock = $this->createMock(Loop::class);
        $loopMock->expects($this->once())->method('add');
        $testConfigMock = $this->createMock(TestConfig::class);

        $testRunner = new AsyncTestRunner($testRunnerMock, $loopMock);
        $testRunner->run($testConfigMock);
    }

    #[Test]
    public function callsProvidedTestRunnerWithAllConfigs(): void
    {
        $expectedCount = 5;
        $testConfigMocks = [];
        for ($i = 0; $i < $expectedCount; $i++) {
            $testConfigMocks[] = $this->createMock(TestConfig::class);
        }

        $loopMock = new SimpleLoop();
        $testRunnerMock = $this->createMock(TestRunner::class);
        $testRunnerMock->expects($this->exactly($expectedCount))->method('run')
            ->willReturnCallback(function () use ($loopMock): void {
                $loopMock->addResult($this->createMock(TestResult::class));
            });


        $testRunner = new AsyncTestRunner($testRunnerMock, $loopMock);
        foreach ($testConfigMocks as $testConfigMock) {
            $testRunner->run($testConfigMock);
        }
        $actual = $testRunner->finalize();

        $this->assertContainsOnly(TestResult::class, $actual);
        $this->assertCount($expectedCount, $actual);
    }
}
