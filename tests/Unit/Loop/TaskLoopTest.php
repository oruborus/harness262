<?php

declare(strict_types=1);

namespace Tests\Unit\Loop;

use Generator;
use Oru\Harness\Contracts\Task;
use Oru\Harness\Loop\TaskLoop;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Throwable;

use function array_fill;
use function chr;
use function count;

#[CoversClass(TaskLoop::class)]
final class TaskLoopTest extends TestCase
{
    /**
     * @param int[] $counts 
     */
    #[Test]
    #[DataProvider('provideConcurrency')]
    public function runsMultipleTasksInAQueueOfDefinedSize(int $concurrency, array $counts, string $expected)
    {
        $actual = '';
        $loop = new TaskLoop($concurrency);
        $currents = array_fill(0, count($counts), 0);

        for ($i = 0; $i < count($counts); $i++) {
            $current = &$currents[$i];
            $count   = $counts[$i];

            $task = $this->createMock(Task::class);
            $task->method('continue')->willReturnCallback(
                static function () use (&$actual, $i, $count, &$current) {
                    if ($count >= $current++) {
                        $actual .= (string) $i;
                    }
                }
            );
            $task->method('done')->willReturnCallback(
                static function () use ($count, &$current) {
                    return $current >= $count;
                }
            );

            $loop->add($task);
        }

        $loop->run();

        $this->assertSame($expected, $actual);
    }

    /**
     * @return Generator<string, array{0: int, 1:int[], 2: string, 3: string}>
     */
    public static function provideConcurrency(): Generator
    {
        yield '1' => [1, [4, 4, 4, 4], '0000111122223333', '0Aa1Bb2Cc3Dd'];
        yield '2' => [2, [4, 4, 4, 4], '0101010123232323', '0Aa1Bb2Cc3Dd'];
        yield '3' => [3, [4, 4, 4, 4], '0120120120123333', '0Aa1Bb2Cc3Dd'];
        yield '4' => [4, [4, 4, 4, 4], '0123012301230123', '0Aa1Bb2Cc3Dd'];
        yield '5' => [5, [4, 4, 4, 4], '0123012301230123', '0Aa1Bb2Cc3Dd'];
        yield '2 different counts' => [2, [5, 1, 1, 1], '01230000', '1Bb2Cc3Dd0Aa'];
        yield '3 different counts' => [3, [5, 2, 3, 2], '012012302300', '1Bb2Cc3Dd0Aa'];
    }

    /**
     * @param int[] $counts 
     */
    #[Test]
    #[DataProvider('provideConcurrency')]
    public function callsOnSuccessCallbackWhenTaskCompletes(int $concurrency, array $counts, string $_, string $expected)
    {
        $actual = '';
        $loop = new TaskLoop($concurrency);
        $currents = array_fill(0, count($counts), 0);

        for ($i = 0; $i < count($counts); $i++) {
            $current = &$currents[$i];
            $count   = $counts[$i];

            $task = $this->createMock(Task::class);
            $task->method('continue')->willReturnCallback(
                static function () use (&$current) {
                    $current++;
                }
            );
            $task->method('done')->willReturnCallback(
                static function () use ($count, &$current) {
                    return $current >= $count;
                }
            );
            $task->method('result')->willReturnCallback(
                static function () use ($i) {
                    return $i;
                }
            );

            $loop->add($task);
        }

        $loop->then(static function (mixed $result) use (&$actual) {
            $actual .= chr($result + 0x30);
        }, fn () => null);
        $loop->then(static function (mixed $result) use (&$actual) {
            $actual .= chr($result + 0x41);
        }, fn () => null);
        $loop->then(static function (mixed $result) use (&$actual) {
            $actual .= chr($result + 0x61);
        }, fn () => null);
        $loop->run();

        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function callsOnExceptionCallbackWhenTaskThrows(): void
    {
        $actual = false;
        $loop = new TaskLoop(8);
        $loop->then(static fn () => null, static function () use (&$actual): void {
            $actual = true;
        });
        $taskStub = $this->createStub(Task::class);
        $taskStub->method('continue')->willReturnCallback(function (): never {
            throw $this->createStub(Throwable::class);
        });
        $taskStub->method('done')->willReturn(true);
        $loop->add($taskStub);

        $loop->run();

        $this->assertTrue($actual);
    }
}
