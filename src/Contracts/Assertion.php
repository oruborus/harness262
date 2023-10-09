<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface Assertion
{
    public function assert(mixed $actual): void;
}
