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

namespace Tests\Utility\Engine\Exception;

use RuntimeException;
use Throwable;

use function getmypid;

final class PidExtractionException extends RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        $message = getmypid()
            ?: throw new RuntimeException('Could not extract pid');

        parent::__construct((string) $message, $code, $previous);
    }
}
