<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

/**
 * @template TResult
 */
interface Loop
{
    /**
     * @param callable():void $task
     */
    public function addTask(callable $task): void;

    public function run(): void;

    public function addResult(TestResult $result): void;

    /**
     * @return TResult
     */
    public function result(): mixed;
}
