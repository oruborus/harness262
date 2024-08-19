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

namespace Oru\Harness\Subprocess;

use Oru\Harness\Contracts\Command;
use Oru\Harness\Contracts\Subprocess;
use Oru\Harness\Contracts\SubprocessFactory;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestResultFactory;
use Symfony\Component\Process\PhpSubprocess as SymfonyPhpSubprocess;

final class PhpSubprocessFactory implements SubprocessFactory
{
    public function __construct(
        private Command $command,
        private TestResultFactory $testResultFactory,
    ) {}

    public function make(TestCase $testCase): Subprocess
    {
        return new PhpSubprocess(
            new SymfonyPhpSubprocess([$this->command]),
            $testCase,
            $this->testResultFactory,
        );
    }
}
