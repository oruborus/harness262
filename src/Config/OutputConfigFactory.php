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

namespace Oru\Harness\Config;

use Oru\Harness\Contracts\ConfigFactory;
use Oru\Harness\Contracts\OutputConfig;
use Oru\Harness\Contracts\OutputType;

final readonly class OutputConfigFactory implements ConfigFactory
{
    public function make(): OutputConfig
    {
        return new class() implements OutputConfig {
            /**
             * @return OutputType[]
             */
            public function outputTypes(): array
            {
                return [OutputType::Console];
            }
        };
    }
}
