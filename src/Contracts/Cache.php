<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface Cache
{
    public function valid(): bool;

    /**
     * @return string[]
     */
    public function usedFiles(): array;

    public function duration(): int;
}