<?php

declare(strict_types=1);

namespace Tests\Harness\Utility\Loop;

use Oru\EcmaScript\Harness\Contracts\Loop;
use Oru\EcmaScript\Harness\Contracts\Task;
use Oru\EcmaScript\Harness\Contracts\TestResult;

/**
 * @implements Loop<TestResult>
 */
final class SimpleLoop implements Loop
{
    /**
     * @var Task[] $tasks
     */
    private array $tasks = [];

    /**
     * @var TestResult[] $result
     */
    private array $result = [];

    public function add(Task $task): void
    {
        $this->tasks[] = $task;
    }

    /**
     * @return TestResult[]
     */
    public function run(): array
    {
        foreach ($this->tasks as $task) {
            $task->continue();
        }

        return $this->result;
    }

    /**
     * @param TestResult
     */
    public function addResult(mixed $result): void
    {
        $this->result[] = $result;
    }
}
