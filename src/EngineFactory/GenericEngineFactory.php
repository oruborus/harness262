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

namespace Oru\Harness\EngineFactory;

use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\Harness\Contracts\EngineFactory;

final class GenericEngineFactory implements EngineFactory
{
    public function __construct(
        private string $path,
    ) {}

    public function make(): Engine
    {
        /** @var Engine */
        return require $this->path;
    }

    public function path(): string
    {
        return $this->path;
    }
}
