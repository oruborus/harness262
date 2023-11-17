<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface ArgumentsParser
{
    public function hasOption(string $option): bool;

    public function getOption(string $option): string;

    /**
     * @return string[]
     */
    public function rest(): array;
}
