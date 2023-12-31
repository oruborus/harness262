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

namespace Tests\Unit\Loop;

use Fiber;
use Oru\Harness\Loop\FiberTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FiberTask::class)]
final class FiberTaskTest extends TestCase
{
    #[Test]
    public function providedFiberWillStart(): void
    {
        $actual = false;
        $fiber = new Fiber(
            static function () use (&$actual): void {
                $actual = true;
            }
        );

        (new FiberTask($fiber))->continue();

        $this->assertTrue($actual);
    }

    #[Test]
    public function providedFiberWillResume(): void
    {
        $actual = false;
        $fiber = new Fiber(
            static function () use (&$actual): void {
                Fiber::suspend();
                $actual = true;
            }
        );
        $fiber->start();

        (new FiberTask($fiber))->continue();

        $this->assertTrue($actual);
    }

    #[Test]
    public function isDoneWhenContainedFiberIsTerminated(): void
    {
        $fiber = new Fiber(
            static function (): void {}
        );
        $fiber->start();

        $actual = (new FiberTask($fiber))->done();

        $this->assertTrue($actual);
    }

    #[Test]
    public function isNotDoneWhenContainedFiberIsNotTerminated(): void
    {
        $fiber = new Fiber(
            static function (): void {}
        );

        $actual = (new FiberTask($fiber))->done();

        $this->assertFalse($actual);
    }

    #[Test]
    public function resultWillContainTheReturnValueOfTheFiberWhenDone(): void
    {
        $expected = 'Correct value';
        $task = new FiberTask(
            new Fiber(
                static function () use ($expected): string {
                    Fiber::suspend('someValue1');
                    Fiber::suspend('someValue2');
                    Fiber::suspend('someValue3');
                    Fiber::suspend('someValue4');
                    return $expected;
                }
            )
        );

        while (!$task->done()) {
            $task->continue();
        }

        $actual = $task->result();

        $this->assertSame($expected, $actual);
    }
}
