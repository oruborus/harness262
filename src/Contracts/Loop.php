<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

use Closure;

interface Loop
{
    public function add(Task $task): void;

    public function then(Closure $onSuccess, Closure $onException): void;

    public function run(): void;
}
