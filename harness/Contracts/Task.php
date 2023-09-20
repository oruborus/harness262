<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface Task
{
    public function continue(): void;

    public function done(): bool;
}
