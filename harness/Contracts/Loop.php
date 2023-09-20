<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

/**
 * @template TResult
 */
interface Loop
{
    public function add(Task $task): void;

    /**
     * @return TResult[]
     */
    public function run(): mixed;

    /**
     * @param TResult $result
     */
    public function addResult(mixed $result): void;
}
