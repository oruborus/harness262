<?php

declare(strict_types=1);

namespace Tests\Utility\Loop;

use Oru\Harness\Contracts\Loop;
use Oru\Harness\Contracts\Task;
use Throwable;

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

    public function run(): void
    {
        foreach ($this->tasks as $task) {
            try {
                while (!$task->done()) {
                    $task->continue();
                }
            } catch (Throwable $throwable) {
                $task->onFailure($throwable);
                continue;
            }
            $task->onSuccess($task->result());
        }
    }
}
