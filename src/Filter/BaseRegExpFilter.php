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

namespace Oru\Harness\Filter;

use Oru\Harness\Contracts\Filter;
use Oru\Harness\Filter\Exception\MalformedRegularExpressionPatternException;
use Oru\Harness\Helpers\ErrorHandler;

use function preg_match;
use function strlen;
use function substr;

abstract class BaseRegExpFilter implements Filter
{
    private const WARNING_PREFIX = 'preg_match(): ';

    /**
     * @var non-empty-string $pattern
     */
    protected readonly string $pattern;

    /**
     * @throws MalformedRegularExpressionPatternException
     */
    public function __construct(
        string $pattern
    ) {
        $this->pattern = "/{$pattern}/";

        $_ = new ErrorHandler(static function (int $_, string $message): never {
            throw new MalformedRegularExpressionPatternException(substr($message, strlen(self::WARNING_PREFIX)));
        }, E_WARNING);

        preg_match($this->pattern, '');
    }
}
