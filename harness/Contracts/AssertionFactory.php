<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

use Oru\EcmaScript\Core\Contracts\Agent;

interface AssertionFactory
{
    public function make(Agent $agent, TestConfig $config): Assertion;
}
