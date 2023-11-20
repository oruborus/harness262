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

use Oru\Harness\Assertion\Exception\AssertionFailedException;
use Oru\Harness\Assertion\Exception\EngineException;
use Oru\Harness\Contracts\Assertion;

final readonly class AssertMultiple implements Assertion
{
    /**
     * @var Assertion[] $assertions
     */
    private array $assertions;

    public function __construct(
        Assertion ...$assertions
    ) {
        $this->assertions = $assertions;
    }

    /**
     * @throws AssertionFailedException
     * @throws EngineException
     */
    public function assert(mixed $actual): void
    {
        foreach ($this->assertions as $assertion) {
            $assertion->assert($actual);
        }
    }

    /**
     * @return Assertion[]
     */
    public function assertions(): array
    {
        return $this->assertions;
    }
}
