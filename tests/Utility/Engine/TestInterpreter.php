<?php

/**
 * Copyright (c) 2023-2024, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Tests\Utility\Engine;

use Oru\EcmaScript\Core\Contracts\Agent;
use Oru\EcmaScript\Core\Contracts\Interpreter;
use Oru\EcmaScript\Core\Contracts\Nodes\Node;
use Oru\EcmaScript\Core\Contracts\Values\Value;
use Oru\EcmaScript\Core\Contracts\Values\ValueFactory;

final class TestInterpreter implements Interpreter
{
    public function run(Node $node, Agent $agent): ?Value
    {
        throw new \RuntimeException('`TestInterpreter::run()` is not implemented');
    }

    public function getValueFactory(): ValueFactory
    {
        return new TestValueFactory();
    }

    public function enterStrictMode(): void
    {
        throw new \RuntimeException('`TestInterpreter::enterStrictMode()` is not implemented');
    }

    public function isInStrictMode(): bool
    {
        throw new \RuntimeException('`TestInterpreter::isInStrictMode()` is not implemented');
    }

    public function leaveStrictMode(): void
    {
        throw new \RuntimeException('`TestInterpreter::leaveStrictMode()` is not implemented');
    }
}
