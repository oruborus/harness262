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

namespace Oru\Harness\Contracts;

enum FrontmatterFlag: string
{
    case onlyStrict       = 'onlyStrict';
    case noStrict         = 'noStrict';
    case module           = 'module';
    case raw              = 'raw';
    case async            = 'async';
    case generated        = 'generated';
    case CanBlockIsFalse  = 'CanBlockIsFalse';
    case CanBlockIsTrue   = 'CanBlockIsTrue';
    case nonDeterministic = 'non-deterministic';
}
