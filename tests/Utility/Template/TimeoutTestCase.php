<?php

/**
 * Copyright (c) 2024, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

$input = fopen('php://stdin', 'r')
    ?: throw new RuntimeException('Could not open STDIN');

/** @psalm-suppress RiskyTruthyFalsyComparison */
stream_get_contents($input)
    ?: throw new RuntimeException('Could not get contents of STDIN');

$start = time();
while (time() - $start <= 2);

throw new Error('This test should have been a timeout');
