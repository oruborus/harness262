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

namespace Oru\Harness\Assertion;

use Oru\EcmaScript\Core\Contracts\Agent;
use Oru\EcmaScript\Core\Contracts\Values\AbruptCompletion;
use Oru\EcmaScript\Core\Contracts\Values\ObjectValue;
use Oru\EcmaScript\Core\Contracts\Values\StringValue;
use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
use Oru\EcmaScript\Core\Contracts\Values\ValueFactory;
use Oru\Harness\Contracts\Assertion;
use Oru\Harness\Assertion\Exception\AssertionFailedException;
use Oru\Harness\Assertion\Exception\EngineException;
use Oru\Harness\Contracts\FrontmatterNegative;
use Throwable;

final readonly class AssertIsThrowableWithConstructor implements Assertion
{
    private StringValue $constructorString;

    private StringValue $nameString;

    public function __construct(
        private Agent $agent,
        ValueFactory $valueFactory,
        private FrontmatterNegative $negative
    ) {
        $this->constructorString = $valueFactory->createString('constructor');
        $this->nameString = $valueFactory->createString('name');
    }

    /**
     * @throws AssertionFailedException
     * @throws EngineException
     */
    public function assert(mixed $actual): void
    {
        if (!$actual instanceof ThrowCompletion) {
            throw new AssertionFailedException('Expected `ThrowCompletion`');
        }
        /** @var Throwable&ThrowCompletion $actual */

        $exception = $actual->getValue();

        if (!$exception instanceof ObjectValue) {
            /** @var bool|float|int|string|null $exceptionValue */
            $exceptionValue = $exception->getValue();
            throw new AssertionFailedException(
                "`ThrowCompletion` does not contain an `ObjectValue`, got '{$exceptionValue}'",
                previous: $actual,
            );
        }

        try {
            $constructor = $exception->get($this->agent, $this->constructorString, $exception);
        } catch (AbruptCompletion $throwable) {
            throw new EngineException('Could not use `get()` to retrieve `constructor`', previous: $throwable);
        }

        if (!$constructor instanceof ObjectValue) {
            throw new AssertionFailedException(
                'Constructor value is not an `ObjectValue`',
                previous: $actual,
            );
        }

        try {
            $hasName = $constructor->hasProperty($this->agent, $this->nameString);
        } catch (AbruptCompletion $throwable) {
            throw new EngineException('Could not use `hasProperty()` to check existence of `name`', previous: $throwable);
        }

        if (!$hasName->getValue()) {
            throw new AssertionFailedException('Constructor does not have a name', previous: $actual,);
        }

        try {
            $nameProperty = $constructor->get($this->agent, $this->nameString, $constructor);
        } catch (AbruptCompletion $throwable) {
            throw new EngineException('Could not use `get()` to retrieve `constructor.name`', previous: $throwable);
        }

        try {
            /** @var bool|float|int|string|null $namePropertyValue */
            $namePropertyValue = $nameProperty->getValue();
            $name = (string) $namePropertyValue;
        } catch (Throwable $throwable) {
            throw new EngineException('Could not convert `name` to string', previous: $throwable);
        }

        if ($this->negative->type() !== $name) {
            throw new AssertionFailedException("Expected `{$this->negative->type()}` but got `{$name}`", previous: $actual);
        }
    }
}
