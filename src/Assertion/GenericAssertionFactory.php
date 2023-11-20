<?php

/**
 * Copyright (c) 2023, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Oru\Harness\Assertion;

use Oru\Harness\Contracts\Assertion;
use Oru\Harness\Contracts\AssertionFactory;
use Oru\Harness\Contracts\Facade;
use Oru\Harness\Contracts\FrontmatterFlag;
use Oru\Harness\Contracts\TestConfig;

use function in_array;

final readonly class GenericAssertionFactory implements AssertionFactory
{
    public function __construct(
        private Facade $facade
    ) {}

    public function make(TestConfig $config): Assertion
    {
        if ($negative = $config->frontmatter()->negative()) {
            return new AssertIsThrowableWithConstructor($this->facade, $negative);
        }

        if (in_array(FrontmatterFlag::async, $config->frontmatter()->flags())) {
            return new AssertMultiple(
                new AssertIsNormal($this->facade),
                new AssertAsync()
            );
        }

        return new AssertIsNormal($this->facade);
    }
}
