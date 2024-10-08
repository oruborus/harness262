<?php

/**
 * Copyright (c) 2023-2024, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Tests\Unit\TestSuite;

use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\TestSuite\GenericTestSuite;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericTestSuite::class)]
final class GenericTestSuiteTest extends TestCase
{
    #[Test]
    public function actsAsValueObject(): void
    {
        $expectedPaths          = ['path/to/file1', 'path/to/file2'];
        $expectedCache          = false;
        $expectedConcurrency    = 123;
        $expectedTestRunnerMode = TestRunnerMode::Linear;
        $expectedStopOnCharacteristic = StopOnCharacteristic::Defect;
        $expectedTimeout        = 123;

        $actual = new GenericTestSuite(
            $expectedPaths,
            $expectedCache,
            $expectedConcurrency,
            $expectedTestRunnerMode,
            $expectedStopOnCharacteristic,
            $expectedTimeout,
        );

        $this->assertSame($expectedPaths, $actual->paths());
        $this->assertSame($expectedCache, $actual->cache());
        $this->assertSame($expectedConcurrency, $actual->concurrency());
        $this->assertSame($expectedTestRunnerMode, $actual->testRunnerMode());
        $this->assertSame($expectedStopOnCharacteristic, $actual->StopOnCharacteristic());
        $this->assertSame($expectedTimeout, $actual->timeout());
    }
}
