<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface Loop
{
    public function add(Task $task): void;

    public function run(): void;
}
