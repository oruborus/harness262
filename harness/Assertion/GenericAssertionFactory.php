<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Assertion;

use Oru\EcmaScript\Core\Contracts\Agent;
use Oru\EcmaScript\Harness\Contracts\Assertion;
use Oru\EcmaScript\Harness\Contracts\AssertionFactory;
use Oru\EcmaScript\Harness\Contracts\TestConfig;

final readonly class GenericAssertionFactory implements AssertionFactory
{
    public function make(Agent $agent, TestConfig $config): Assertion
    {
        if ($negative = $config->frontmatter()->negative()) {
            return new AssertIsThrowableWithConstructor($agent, $negative);
        }

        return new AssertIsNotThrowable($agent);
    }
}
