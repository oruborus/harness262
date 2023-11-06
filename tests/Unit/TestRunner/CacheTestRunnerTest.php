<?php

declare(strict_types=1);

namespace Tests\Unit\TestRunner;

use Oru\Harness\Contracts\CacheRepository;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Contracts\TestRunner;
use Oru\Harness\TestRunner\CacheTestRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CacheTestRunner::class)]
final class CacheTestRunnerTest extends TestCase
{
    #[Test]
    public function retrievesTestResultFromCache(): void
    {
        $repetitions = 5;
        $cacheRepositoryStub = $this->createConfiguredStub(CacheRepository::class, [
            'get' => $this->createStub(TestResult::class)
        ]);
        $testRunnerMock = $this->createMock(TestRunner::class);
        $testRunnerMock->expects($this->never())->method('add');
        $testRunner = new CacheTestRunner($cacheRepositoryStub, $testRunnerMock);

        for ($i = 0; $i < $repetitions; $i++) {
            $testRunner->add($this->createStub(TestConfig::class));
        }

        $actual = $testRunner->run();

        $this->assertCount($repetitions, $actual);
        for ($i = 0; $i < $repetitions; $i++) {
            $this->assertSame(TestResultState::Cache, $actual[$i]->state());
            $this->assertSame(0, $actual[$i]->duration());
        }
    }

    #[Test]
    public function preparesSecondaryTestRunnerToRetrieveResultWhenNoCacheIsAvailable(): void
    {
        $cacheRepositoryStub = $this->createConfiguredStub(CacheRepository::class, [
            'get' => null
        ]);
        $testRunnerMock = $this->createMock(TestRunner::class);
        $testRunnerMock->expects($this->once())->method('add');
        $testRunner = new CacheTestRunner($cacheRepositoryStub, $testRunnerMock);

        $testRunner->add($this->createStub(TestConfig::class));
    }
}
