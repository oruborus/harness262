<?php

/**
 * Copyright (c) 2023-2025, Felix Jahn
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

use Oru\Harness\Assertion\Exception\EngineException;
use Oru\Harness\Contracts\Assertion;
use Oru\Harness\Contracts\AssertionFactory;
use Oru\Harness\Contracts\FrontmatterFlag;
use Oru\Harness\Contracts\TestCase;

use function in_array;

final readonly class GenericAssertionFactory implements AssertionFactory
{
    /** @throws EngineException */
    public function make(TestCase $testCase): Assertion
    {
        if ($negative = $testCase->frontmatter()->negative()) {
            return new AssertIsThrowableWithConstructor($negative);
        }

        if (in_array(FrontmatterFlag::async, $testCase->frontmatter()->flags())) {
            return new AssertAsync(new AssertIsNormal());
        }

        return new AssertIsNormal();
    }
}
