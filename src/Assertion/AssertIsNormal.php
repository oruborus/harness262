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
use Oru\Harness\Contracts\Facade;
use Throwable;

use function is_null;

final readonly class AssertIsNormal implements Assertion
{
    public function __construct(
        private Facade $facade
    ) {}

    /**
     * @throws AssertionFailedException
     * @throws EngineException
     *
     * @psalm-suppress MixedAssignment  The methods of `Facade` intentionally return `mixed`
     */
    public function assert(mixed $actual): void
    {
        if ($this->facade->isNormalCompletion($actual)) {
            return;
        }

        if (!$this->facade->isThrowCompletion($actual)) {
            throw new AssertionFailedException('Expected `NormalCompletion`');
        }

        $value = $this->facade->completionGetValue($actual);

        if (!$this->facade->isObject($value)) {
            throw new AssertionFailedException((string) $value);
        }

        try {
            $message = $this->facade->objectGetAsString($value, 'message');
        } catch (Throwable $throwable) {
            throw new EngineException('Could not convert object property `message` to string', previous: $throwable);
        }

        if (is_null($message)) {
            throw new EngineException('Object property `message` was empty');
        }

        throw new AssertionFailedException($message);
    }
}
