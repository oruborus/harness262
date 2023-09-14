<?php

declare(strict_types=1);

namespace Tests\Harness\Utility\Loop;

use Oru\EcmaScript\Harness\Contracts\Loop;
use Oru\EcmaScript\Harness\Contracts\TestResult;

/**
 * @implements Loop<TestResult>
 */
final class SimpleLoop implements Loop
{
    /**
     * @var (callable():void)[] $tasks
     */
    private array $tasks = [];

    /**
     * @var TestResult[] $result
     */
    private array $result = [];

    /**
     * @param callable():void $task
     */
    public function addTask(callable $task): void
    {
        $this->tasks[] = $task;
    }

    /**
     * @return TestResult[]
     */
    public function run(): array
    {
        foreach ($this->tasks as $task) {
            $task();
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
