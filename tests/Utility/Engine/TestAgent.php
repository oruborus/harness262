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

namespace Tests\Utility\Engine;

use DI\Container;
use Oru\EcmaScript\Core\Contracts\Agent;
use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\EcmaScript\Core\Contracts\Values\ExecutionContext;
use Oru\EcmaScript\Core\Contracts\Parser;
use Oru\EcmaScript\Core\Contracts\Interpreter;
use Oru\EcmaScript\Core\Contracts\Position;
use Oru\EcmaScript\Core\Contracts\Values\ListValue;
use Oru\EcmaScript\Core\Contracts\Values\ObjectValue;
use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
use Oru\EcmaScript\Core\Contracts\Values\BooleanValue;
use Oru\EcmaScript\Core\Contracts\Values\SourceText;
use Oru\EcmaScript\Core\Contracts\WellKnownSymbol;
use Oru\EcmaScript\Core\Contracts\Values\SymbolValue;

use function is_string;

final class TestAgent implements Agent
{
    public function __construct(
        private Container $container = new Container(),
    ) {}

    public function setStrict(bool $strict): void {}

    public function isStrictCode(): bool
    {
        return false;
    }

    public function setInEval(bool $inEval): void {}

    public function inEval(): bool
    {
        return false;
    }

    /** @param string[] $currentLabelSet */
    public function setCurrentLabelSet(array $currentLabelSet): void {}

    /** @return string[] */
    public function currentLabelSet(): array
    {
        return [];
    }

    public function setInIterationOrSwitchStatement(bool $inIterationOrSwitchStatement): void {}

    public function inIterationOrSwitchStatement(): bool
    {
        return false;
    }

    public function setCurrentSourceText(?SourceText $sourceText = null): void {}

    public function getCurrentSourceText(): ?SourceText
    {
        return null;
    }

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

    public function createSyntaxError(string $message, ?Position $position = null): ThrowCompletion
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
        return $this->container->get($abstract);
    }

    public function make(object|string $concrete, array $parameters = []): object
    {
        return $this->container->make($concrete, $parameters);
    }

    public function call(callable $function, array $parameters = []): mixed
    {
        return $this->container->call($function, $parameters);
    }

    public function bind(string $abstract, object|string|callable $concrete): void
    {
        if (is_string($concrete)) {
            $concrete = $this->container->make($concrete);
        }

        $this->container->set($abstract, $concrete);
    }

    public function singleton(string $abstract, object|string|callable $concrete): void
    {
        $this->bind($abstract, $concrete);
    }
}
