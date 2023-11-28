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

namespace Tests\Unit\TestResult;

use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\TestResult\GenericTestResult;
use Oru\Harness\TestResult\GenericTestResultFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversClass(GenericTestResultFactory::class)]
final class GenericTestResultFactoryTest extends TestCase
{
    #[Test]
    public function createsATestResultForASkippedTest(): void
    {
        $expectedPath = 'path/to/some/test/file';

        $factory = new GenericTestResultFactory();
        $actual = $factory->makeSkipped($expectedPath);

        $this->assertInstanceOf(GenericTestResult::class, $actual);
        $this->assertSame(TestResultState::Skip, $actual->state());
        $this->assertSame($expectedPath, $actual->path());
        $this->assertSame([], $actual->usedFiles());
        $this->assertSame(0, $actual->duration());
        $this->assertSame(null, $actual->throwable());
    }

    #[Test]
    public function createsATestResultForACachedTest(): void
    {
        $expectedPath = 'path/to/some/test/file';
        $expectedUsedFiles = ['A', 'B'];

        $factory = new GenericTestResultFactory();
        $actual = $factory->makeCached($expectedPath, $expectedUsedFiles);

        $this->assertInstanceOf(GenericTestResult::class, $actual);
        $this->assertSame(TestResultState::Cache, $actual->state());
        $this->assertSame($expectedPath, $actual->path());
        $this->assertSame($expectedUsedFiles, $actual->usedFiles());
        $this->assertSame(0, $actual->duration());
        $this->assertSame(null, $actual->throwable());
    }

    #[Test]
    public function createsATestResultForAnErroredTest(): void
    {
        $expectedPath = 'path/to/some/test/file';
        $expectedUsedFiles = ['A', 'B'];
        $expectedDuration = 123;
        $expectedThrowable = $this->createStub(Throwable::class);

        $factory = new GenericTestResultFactory();
        $actual = $factory->makeErrored($expectedPath, $expectedUsedFiles, $expectedDuration, $expectedThrowable);

        $this->assertInstanceOf(GenericTestResult::class, $actual);
        $this->assertSame(TestResultState::Error, $actual->state());
        $this->assertSame($expectedPath, $actual->path());
        $this->assertSame($expectedUsedFiles, $actual->usedFiles());
        $this->assertSame($expectedDuration, $actual->duration());
        $this->assertSame($expectedThrowable, $actual->throwable());
    }

    #[Test]
    public function createsATestResultForAFailedTest(): void
    {
        $expectedPath = 'path/to/some/test/file';
        $expectedUsedFiles = ['A', 'B'];
        $expectedDuration = 123;
        $expectedThrowable = $this->createStub(Throwable::class);

        $factory = new GenericTestResultFactory();
        $actual = $factory->makeFailed($expectedPath, $expectedUsedFiles, $expectedDuration, $expectedThrowable);

        $this->assertInstanceOf(GenericTestResult::class, $actual);
        $this->assertSame(TestResultState::Fail, $actual->state());
        $this->assertSame($expectedPath, $actual->path());
        $this->assertSame($expectedUsedFiles, $actual->usedFiles());
        $this->assertSame($expectedDuration, $actual->duration());
        $this->assertSame($expectedThrowable, $actual->throwable());
    }

    #[Test]
    public function createsATestResultForASuccessfulTest(): void
    {
        $expectedPath = 'path/to/some/test/file';
        $expectedUsedFiles = ['A', 'B'];
        $expectedDuration = 123;

        $factory = new GenericTestResultFactory();
        $actual = $factory->makeSuccessful($expectedPath, $expectedUsedFiles, $expectedDuration);

        $this->assertInstanceOf(GenericTestResult::class, $actual);
        $this->assertSame(TestResultState::Success, $actual->state());
        $this->assertSame($expectedPath, $actual->path());
        $this->assertSame($expectedUsedFiles, $actual->usedFiles());
        $this->assertSame($expectedDuration, $actual->duration());
        $this->assertSame(null, $actual->throwable());
    }
}
