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
use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\EcmaScript\Core\Contracts\Values\ExecutionContext;
use Oru\EcmaScript\Core\Contracts\Parser;
use Oru\EcmaScript\Core\Contracts\Interpreter;
use Oru\EcmaScript\Core\Contracts\Values\ListValue;
use Oru\EcmaScript\Core\Contracts\Values\ObjectValue;
use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
use Oru\EcmaScript\Core\Contracts\PositionalInformation;
use Oru\EcmaScript\Core\Contracts\Values\BooleanValue;
use Oru\EcmaScript\Core\Contracts\WellKnownSymbol;
use Oru\EcmaScript\Core\Contracts\Values\SymbolValue;

final class TestAgent implements Agent
{
    public function setCurrentFile(?string $file = null): void
    {
        throw new \RuntimeException('`TestAgent::setCurrentFile()` is not implemented');
    }

    public function getCurrentFile(): ?string
    {
        throw new \RuntimeException('`TestAgent::getCurrentFile()` is not implemented');
    }

    public function engine(): Engine
    {
        throw new \RuntimeException('`TestAgent::engine()` is not implemented');
    }

    public function getRunningExecutionContext(): ExecutionContext
    {
        throw new \RuntimeException('`TestAgent::getRunningExecutionContext()` is not implemented');
    }

    public function getExecutionContextStack(): array
    {
        throw new \RuntimeException('`TestAgent::getExecutionContextStack()` is not implemented');
    }

    public function popExecutionContextStack(ExecutionContext $executionContext): ExecutionContext
    {
        throw new \RuntimeException('`TestAgent::popExecutionContextStack()` is not implemented');
    }

    public function pushExecutionContextStack(ExecutionContext $executionContext): void
    {
        throw new \RuntimeException('`TestAgent::pushExecutionContextStack()` is not implemented');
    }

    public function getParser(): Parser
    {
        throw new \RuntimeException('`TestAgent::getParser()` is not implemented');
    }

    public function setParser(Parser $parser): void
    {
        throw new \RuntimeException('`TestAgent::setParser()` is not implemented');
    }

    public function setWellKnownSymbols(array $wellKnownSymbols): void
    {
        throw new \RuntimeException('`TestAgent::setWellKnownSymbols()` is not implemented');
    }

    public function getInterpreter(): Interpreter
    {
        return new TestInterpreter();
    }

    public function getGlobalSymbolRegistry(): ListValue
    {
        throw new \RuntimeException('`TestAgent::getGlobalSymbolRegistry()` is not implemented');
    }

    public function createError(string $type = 'AggregateError', string $message = '', ?ObjectValue $errors = null): ObjectValue
    {
        throw new \RuntimeException('`TestAgent::createError()` is not implemented');
    }

    public function createErrorThrowCompletion(string $type = 'AggregateError', string $message = '', ?ObjectValue $errors = null): ThrowCompletion
    {
        throw new \RuntimeException('`TestAgent::createErrorThrowCompletion()` is not implemented');
    }

    public function createSyntaxError(string $message, ?PositionalInformation $positionalInformation = null): ThrowCompletion
    {
        throw new \RuntimeException('`TestAgent::createSyntaxError()` is not implemented');
    }

    public function getLittleEndian(): BooleanValue
    {
        throw new \RuntimeException('`TestAgent::getLittleEndian()` is not implemented');
    }

    public function getWellKnownSymbol(WellKnownSymbol $wellKnownSymbol): SymbolValue
    {
        throw new \RuntimeException('`TestAgent::getWellKnownSymbol()` is not implemented');
    }

    public function getSpecificationNameForWellKnownSymbol(SymbolValue $symbol): ?string
    {
        throw new \RuntimeException('`TestAgent::getSpecificationNameForWellKnownSymbol()` is not implemented');
    }

    public function get(string $abstract, array $parameters = []): object
    {
        throw new \RuntimeException('`TestAgent::get()` is not implemented');
    }

    public function make(object|string $concrete, array $parameters = []): object
    {
        throw new \RuntimeException('`TestAgent::make()` is not implemented');
    }

    public function call(callable $function, array $parameters = []): mixed
    {
        throw new \RuntimeException('`TestAgent::call()` is not implemented');
    }

    public function bind(string $abstract, object|string $concrete): void
    {
        throw new \RuntimeException('`TestAgent::bind()` is not implemented');
    }

    public function singleton(string $abstract, object|string $concrete): void
    {
        throw new \RuntimeException('`TestAgent::singleton()` is not implemented');
    }
}
