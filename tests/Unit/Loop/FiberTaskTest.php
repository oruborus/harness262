<?php

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
            static function (): void {
            }
        );
        $fiber->start();

        $actual = (new FiberTask($fiber))->done();

        $this->assertTrue($actual);
    }

    #[Test]
    public function isNotDoneWhenContainedFiberIsNotTerminated(): void
    {
        $fiber = new Fiber(
            static function (): void {
            }
        );

        $actual = (new FiberTask($fiber))->done();

        $this->assertFalse($actual);
    }
}
