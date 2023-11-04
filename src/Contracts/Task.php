<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface Task
{
    public function continue(): void;

    public function done(): bool;

    public function result(): mixed;
}
