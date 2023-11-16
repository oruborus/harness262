<?php

declare(strict_types=1);

namespace Oru\Harness\Loop;

use Closure;
use Fiber;
use Oru\Harness\Contracts\Task;

final readonly class FiberTask implements Task
{
    public function __construct(
        private Fiber $fiber,
        private ?Closure $onSuccess = null,
        private ?Closure $onFailure = null
    ) {
    }

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
