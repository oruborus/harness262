<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Loop;

use Fiber;
use Generator;
use Oru\EcmaScript\Harness\Loop\TaskLoop;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function count;

#[CoversClass(TaskLoop::class)]
final class TaskLoopTest extends TestCase
{
    /**
     * @param int[] $counts 
     */
    #[Test]
    #[DataProvider('provideConcurency')]
    public function runsMultipleCallablesInAQueueOfDefinedSize(int $concurency, array $counts, string $expected)
    {
        $callableFactory = static function (int $id, int $count, string &$actual): callable {
            return static function () use ($id, $count, &$actual): void {
                for ($i = 0; $i < $count; $i++) {
                    $actual .= (string) $id;
                    Fiber::suspend();
                }
            };
        };
        $actual = '';

        $loop = new TaskLoop($concurency);
        for ($i = 0; $i < count($counts); $i++) {
            $loop->addTask($callableFactory($i, $counts[$i], $actual));
        }
        $loop->run();

        $this->assertSame($expected, $actual);
    }

    /**
     * @return Generator<string, array{0: int, 1: string}>
     */
    public static function provideConcurency(): Generator
    {
        yield '1' => [1, [4, 4, 4, 4], '0000111122223333'];
        yield '2' => [2, [4, 4, 4, 4], '0101010123232323'];
        yield '3' => [3, [4, 4, 4, 4], '0120120120123333'];
        yield '4' => [4, [4, 4, 4, 4], '0123012301230123'];
        yield '5' => [5, [4, 4, 4, 4], '0123012301230123'];
        yield '2 different counts' => [2, [5, 1, 1, 1], '01020300'];
        yield '3 different counts' => [3, [5, 2, 3, 2], '012012032030'];
    }
}
