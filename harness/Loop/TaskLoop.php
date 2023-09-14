<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Loop;

use Fiber;
use Oru\EcmaScript\Harness\Contracts\Loop;
use Oru\EcmaScript\Harness\Contracts\TestResult;

use function array_shift;
use function count;

/**
 * @implements Loop<TestResult>
 */
final class TaskLoop implements Loop
{
    /** @var Fiber[] $fibers */
    private array $fibers = [];

    /** @var TestResult[] $results */
    private array $results = [];

    public function __construct(
        private int $concurrency
    ) {
    }

    /**
     * @param callable():void $task
     */
    public function addTask(callable $task): void
    {
        $fiber = new Fiber($task);
        $this->fibers[] = $fiber;
    }

    public function run(): void
    {
        $count = 0;
        $stash = [];
        while ($current = array_shift($this->fibers)) {
            if (!$current->isStarted()) {
                $current->start();
            } elseif ($current->isSuspended()) {
                $current->resume();
            }

            if (!$current->isTerminated()) {
                $stash[] = $current;
                $count++;
            }

            if ($count === $this->concurrency || count($this->fibers) === 0) {
                $this->fibers = [...$stash, ...$this->fibers];
                $count = 0;
                $stash = [];
            }
        }
    }

    /**
     * @param TestResult $result
     */
    public function addResult(mixed $result): void
    {
        $this->results[] = $result;
    }

    /**
     * @return TestResult[]
     */
    public function results(): array
    {
        return $this->results;
    }
}
