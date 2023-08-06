<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface Output
{
    public function write(string $content): void;

    public function writeLn(string $content): void;
}
