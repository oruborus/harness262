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

namespace Oru\Harness\Loop;

use Closure;
use Oru\Harness\Contracts\Loop;
use Oru\Harness\Contracts\Task;
use Throwable;

use function array_shift;
use function count;

final class TaskLoop implements Loop
{
    /** @var Task[] $tasks */
    private array $tasks = [];

    public function __construct(
        private int $concurrency
    ) {}

    public function add(Task $task): void
    {
        $this->tasks[] = $task;
    }

    public function run(): void
    {
        $count = 0;
        $stash = [];
        while ($current = array_shift($this->tasks)) {
            try {
                $current->continue();
            } catch (Throwable $throwable) {
                $current->onFailure($throwable);
                continue;
            }

            if (!$current->done()) {
                $stash[] = $current;
                $count++;
            } else {
                $current->onSuccess($current->result());
            }

            if ($count === $this->concurrency || count($this->tasks) === 0) {
                $this->tasks = [...$stash, ...$this->tasks];
                $count = 0;
                $stash = [];
            }
        }
    }
}
