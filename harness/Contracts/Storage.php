<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface Storage
{
    public function put(string $key, mixed $content): void;

    public function get(string $key): mixed;
}
