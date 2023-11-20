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

namespace Tests\Utility\Loop;

use Oru\Harness\Contracts\Loop;
use Oru\Harness\Contracts\Task;
use Throwable;

final class SimpleLoop implements Loop
{
    /**
     * @var Task[] $tasks
     */
    private array $tasks = [];

    public function add(Task $task): void
    {
        $this->tasks[] = $task;
    }

    public function run(): void
    {
        foreach ($this->tasks as $task) {
            try {
                while (!$task->done()) {
                    $task->continue();
                }
            } catch (Throwable $throwable) {
                $task->onFailure($throwable);
                continue;
            }
            $task->onSuccess($task->result());
        }
    }
}
