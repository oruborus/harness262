<?php

declare(strict_types=1);

namespace Tests\Unit\TestRunner;

use Oru\Harness\Contracts\Loop;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestRunner;
use Oru\Harness\Loop\FiberTask;
use Oru\Harness\TestRunner\AsyncTestRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Tests\Utility\Loop\SimpleLoop;

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
        $testRunner->add($testConfigMock);
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
        $testRunnerMock->expects($this->exactly($expectedCount))->method('add');

        $testRunner = new AsyncTestRunner($testRunnerMock, $loopMock);
        foreach ($testConfigMocks as $testConfigMock) {
            $testRunner->add($testConfigMock);
        }


        $testRunner->run();
    }
}
