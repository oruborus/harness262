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

namespace Oru\Harness\Output;

use Oru\Harness\Contracts\OutputConfig;
use Oru\Harness\Contracts\Output;
use Oru\Harness\Contracts\OutputFactory;

final readonly class GenericOutputFactory implements OutputFactory
{
    public function make(OutputConfig $config): Output
    {
        return new ConsoleOutput();
    }
}
