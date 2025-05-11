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

try {
    new class {
        public function __construct()
        {
            (function () {
                throw new ErrorException('THROWN IN TEST');
            })();
        }
    };
} catch (ErrorException $exception) {
    echo serialize($exception);
}
