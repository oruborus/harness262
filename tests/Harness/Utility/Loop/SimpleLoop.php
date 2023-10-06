<?php

declare(strict_types=1);

namespace Tests\Harness\Utility\Loop;

use Oru\EcmaScript\Harness\Contracts\Loop;
use Oru\EcmaScript\Harness\Contracts\Task;

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
            $task->continue();
        }
    }
}
