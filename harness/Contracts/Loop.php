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

    /**
     * @param TResult $result
     */
    public function addResult(mixed $result): void;

    /**
     * @return TResult[]
     */
    public function results(): mixed;
}
