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

use Oru\Harness\Contracts\CacheRepository;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultFactory;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Contracts\TestRunner;
use Oru\Harness\TestRunner\CacheTestRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

#[CoversClass(CacheTestRunner::class)]
final class CacheTestRunnerTest extends PHPUnitTestCase
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
        $testResultFactoryStub = $this->createConfiguredStub(TestResultFactory::class, [
            'makeCached' => $this->createConfiguredStub(TestResult::class, ['state' => TestResultState::Cache])
        ]);
        $testRunner = new CacheTestRunner($cacheRepositoryStub, $testRunnerMock, $testResultFactoryStub);

        for ($i = 0; $i < $repetitions; $i++) {
            $testRunner->add($this->createStub(TestCase::class));
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
        $testRunner = new CacheTestRunner($cacheRepositoryStub, $testRunnerMock, $this->createStub(TestResultFactory::class));

        $testRunner->add($this->createStub(TestCase::class));
    }
}
