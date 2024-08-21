<?php

/**
 * Copyright (c) 2023-2024, Felix Jahn
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

use function is_string;
use function str_starts_with;
use function substr;

final readonly class AssertAsync implements Assertion
{
    private const SUCCESS_SEQUENCE = 'Test262:AsyncTestComplete';

    private const FAILURE_SEQUENCE = 'Test262:AsyncTestFailure: ';

    private const FAILURE_SEQUENCE_LENGTH = 26;

    /**
     * @throws AssertionFailedException
     * @throws EngineException
     */
    public function assert(mixed $actual): void
    {
        if (!is_string($actual)) {
            throw new EngineException('Expected string output');
        }

        if (str_starts_with($actual, static::SUCCESS_SEQUENCE)) {
            return;
        }

        if (!str_starts_with($actual, static::FAILURE_SEQUENCE)) {
            throw new EngineException("Expected string output to start with `" . static::FAILURE_SEQUENCE . "` in case of failure, got: \"{$actual}\"");
        }

        $message = substr($actual, static::FAILURE_SEQUENCE_LENGTH);

        throw new AssertionFailedException($message);
    }
}
