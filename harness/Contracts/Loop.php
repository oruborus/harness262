<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface Loop
{
    public function add(Task $task): void;

    public function run(): void;
}
