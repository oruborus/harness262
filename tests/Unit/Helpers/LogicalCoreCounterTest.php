<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use Fidry\CpuCoreCounter\CpuCoreCounter;
use Fidry\CpuCoreCounter\NumberOfCpuCoreNotFound;
use Oru\Harness\Helpers\LogicalCoreCounter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(LogicalCoreCounter::class)]
final class LogicalCoreCounterTest extends TestCase
{
    #[Test]
    public function returnsTheNumberOfLogicalCoresOfTheMachine(): void
    {
        try {
            $expected = (new CpuCoreCounter())->getCount();
        } catch (NumberOfCpuCoreNotFound) {
            $expected = 999;
        }

        $coreCounter = new LogicalCoreCounter();
        $actual = $coreCounter->count();

        $this->assertSame($expected, $actual);
    }
}
