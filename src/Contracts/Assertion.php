<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

use Oru\Harness\Assertion\Exception\AssertionFailedException;
use Oru\Harness\Assertion\Exception\EngineException;

interface Assertion
{
    /**
     * @throws AssertionFailedException
     * @throws EngineException
     */
    public function assert(mixed $actual): void;
}
