<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface AssertionFactory
{
    public function make(TestConfig $config): Assertion;
}
