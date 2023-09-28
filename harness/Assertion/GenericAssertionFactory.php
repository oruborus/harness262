<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Assertion;

use Oru\EcmaScript\Harness\Contracts\Assertion;
use Oru\EcmaScript\Harness\Contracts\AssertionFactory;
use Oru\EcmaScript\Harness\Contracts\Facade;
use Oru\EcmaScript\Harness\Contracts\TestConfig;

final readonly class GenericAssertionFactory implements AssertionFactory
{
    public function __construct(
        private Facade $facade
    ) {
    }

    public function make(TestConfig $config): Assertion
    {
        if ($negative = $config->frontmatter()->negative()) {
            return new AssertIsThrowableWithConstructor($this->facade, $negative);
        }

        return new AssertIsNormal($this->facade);
    }
}
