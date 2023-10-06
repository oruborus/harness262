<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Loop;

use Oru\EcmaScript\Harness\Contracts\Loop;
use Oru\EcmaScript\Harness\Contracts\Task;

use function array_shift;
use function count;

final class TaskLoop implements Loop
{
    /** @var Task[] $tasks */
    private array $tasks = [];

    public function __construct(
        private int $concurrency
    ) {
    }

    public function add(Task $task): void
    {
        $this->tasks[] = $task;
    }

    public function run(): void
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
    }
}
