<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Loop;

use Oru\EcmaScript\Harness\Contracts\Loop;
use Oru\EcmaScript\Harness\Contracts\Task;
use Oru\EcmaScript\Harness\Contracts\TestResult;

use function array_shift;
use function count;

/**
 * @implements Loop<TestResult>
 */
final class TaskLoop implements Loop
{
    /** @var Task[] $tasks */
    private array $tasks = [];

    /** @var TestResult[] $results */
    private array $results = [];

    public function __construct(
        private int $concurrency
    ) {
    }

    public function add(Task $task): void
    {
        $this->tasks[] = $task;
    }

    /**
     * @return TestResult[]
     */
    public function run(): array
    {
        $count = 0;
        $stash = [];
        while ($current = array_shift($this->tasks)) {
            $current->continue();

            if (!$current->done()) {
                $stash[] = $current;
                $count++;
            }

            if ($count === $this->concurrency || count($this->tasks) === 0) {
                $this->tasks = [...$stash, ...$this->tasks];
                $count = 0;
                $stash = [];
            }
        }

        return $this->results;
    }

    /**
     * @param TestResult $result
     */
    public function addResult(mixed $result): void
    {
        $this->results[] = $result;
    }
}
