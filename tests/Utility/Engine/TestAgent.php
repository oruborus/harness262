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
use Oru\EcmaScript\Core\Contracts\Values\ExecutionContext;
use Oru\EcmaScript\Core\Contracts\Values\ListValue;
use Oru\EcmaScript\Core\Contracts\Values\BooleanValue;
use Oru\EcmaScript\Core\Contracts\Values\GoalSymbol;
use Oru\EcmaScript\Core\Contracts\Values\SourceText;
use Oru\EcmaScript\Core\Contracts\WellKnownSymbol;
use Oru\EcmaScript\Core\Contracts\Values\SymbolValue;
use Oru\EcmaScript\Core\Contracts\Values\ValueFactory;

use function is_string;

final class TestAgent implements Agent
{
    public readonly BooleanValue $littleEndian;

    public BooleanValue $canBlock;

    public readonly string $signifier;

    public readonly BooleanValue $isLockFree1;

    public readonly BooleanValue $isLockFree2;

    public readonly BooleanValue $isLockFree8;

    // FIXME: Implement Agent::[[CandidateExecution]]
    // public CandidateExecutionRecord $candidateExecution ;

    /** @var ListValue<ObjectValue|SymbolValue> $keptAlive */
    public ListValue $keptAlive;

    public int $moduleAsyncEvaluationCount;

    public GoalSymbol $goalSymbol;

    public ?string $currentFile = null;

    public bool $strict = false;

    public bool $inEval = false;

    /** @var string[] $currentLabelSet */
    public array $currentLabelSet = [];

    public bool $inIterationStatement = false;

    public bool $inSwitchStatement = false;

    public ?SourceText $currentSourceText = null;

    public ListValue $globalSymbolRegistry;

    /** @param ListValue<array{key: StringValue, symbol: SymbolValue}> $globalSymbolRegistry */
    public function __construct(
        private Container $container = new Container(),
    ) {}

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

    public function setWellKnownSymbols(array $wellKnownSymbols): void
    {
        throw new \RuntimeException('`TestAgent::setWellKnownSymbols()` is not implemented');
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
