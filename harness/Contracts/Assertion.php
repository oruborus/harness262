<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface Assertion
{
    public function assert(mixed $actual): void;
}
