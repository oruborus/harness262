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

namespace Tests\Utility\Facade;

use Tests\Utility\Facade\Exception\PidExtractionException;

use function getmypid;
use function file_get_contents;
use function file_put_contents;
use function str_replace;

final class ProcessTestFacade extends TestFacade
{
    public function __construct(
        private int $parentPid,
        private string $path = 'Harness.php'
    ) {
        $fileContent = file_get_contents($this->path);

        $replace = (string) (getmypid() ?: -2);
        $updatedContent = str_replace((string) $parentPid, $replace, $fileContent);

        file_put_contents($this->path, $updatedContent);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function engineRun(): mixed
    {
        $pid = getmypid() ?: -1;
        throw new PidExtractionException("{$this->parentPid} {$pid}");
    }
}
