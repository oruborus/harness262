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

namespace Oru\Harness\Loop;

use Closure;
use Fiber;
use Oru\Harness\Contracts\Task;

final readonly class FiberTask implements Task
{
    /** @param Fiber<mixed, mixed, mixed, mixed> $fiber */
    public function __construct(
        private Fiber $fiber,
        private ?Closure $onSuccess = null,
        private ?Closure $onFailure = null
    ) {}

    public function continue(): void
    {
        if (!$this->fiber->isStarted()) {
            $this->fiber->start();
            return;
        }

        if ($this->fiber->isSuspended()) {
            $this->fiber->resume();
            return;
        }
    }

    public function done(): bool
    {
        return $this->fiber->isTerminated();
    }

    public function result(): mixed
    {
        return $this->fiber->getReturn();
    }

    public function onSuccess(mixed ...$arguments): mixed
    {
        if ($this->onSuccess) {
            return ($this->onSuccess)(...$arguments);
        }

        return null;
    }

    public function onFailure(mixed ...$arguments): mixed
    {
        if ($this->onFailure) {
            return ($this->onFailure)(...$arguments);
        }

        return null;
    }
}
