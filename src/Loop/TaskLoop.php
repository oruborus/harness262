<?php

declare(strict_types=1);

namespace Oru\Harness\Loop;

use Closure;
use Oru\Harness\Contracts\Loop;
use Oru\Harness\Contracts\Task;

use function array_shift;
use function count;

final class TaskLoop implements Loop
{
    /** @var Task[] $tasks */
    private array $tasks = [];

    /** @var Closure[] $callbacks */
    private array $callbacks = [];

    public function __construct(
        private int $concurrency
    ) {
    }

    public function add(Task $task): void
    {
        $this->tasks[] = $task;
    }

    public function then(Closure $callback): void
    {
        $this->callbacks[] = $callback;
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
            } else {
                foreach ($this->callbacks as $callback) {
                    $callback($current->result());
                }
            }

            if ($count === $this->concurrency || count($this->tasks) === 0) {
                $this->tasks = [...$stash, ...$this->tasks];
                $count = 0;
                $stash = [];
            }
        }
    }
}
