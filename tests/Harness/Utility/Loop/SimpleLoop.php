<?php

declare(strict_types=1);

namespace Tests\Harness\Utility\Loop;

use Oru\EcmaScript\Harness\Contracts\Loop;
use Oru\EcmaScript\Harness\Contracts\TestResult;

/**
 * @implements Loop<TestResult[]>
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

    public function run(): void
    {
        foreach ($this->tasks as $task) {
            $task();
        }
    }

    public function addResult(TestResult $result): void
    {
        $this->result[] = $result;
    }

    /**
     * @return TestResult[]
     */
    public function result(): array
    {
        return $this->result;
    }
}
