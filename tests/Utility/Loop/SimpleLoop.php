<?php

declare(strict_types=1);

namespace Tests\Utility\Loop;

use Closure;
use Oru\Harness\Contracts\Loop;
use Oru\Harness\Contracts\Task;

final class SimpleLoop implements Loop
{
    /**
     * @var Task[] $tasks
     */
    private array $tasks = [];

    public function add(Task $task): void
    {
        $this->tasks[] = $task;
    }

    public function then(Closure $_, Closure $__): void
    {
    }

    public function run(): void
    {
        foreach ($this->tasks as $task) {
            $task->continue();
        }
    }
}
