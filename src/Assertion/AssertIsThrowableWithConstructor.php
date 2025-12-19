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

use Oru\EcmaScript\Core\Contracts\Values\AbruptCompletion;
use Oru\EcmaScript\Core\Contracts\Values\ObjectValue;
use Oru\EcmaScript\Core\Contracts\Values\StringValue;
use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
use Oru\Harness\Contracts\Assertion;
use Oru\Harness\Assertion\Exception\AssertionFailedException;
use Oru\Harness\Assertion\Exception\EngineException;
use Oru\Harness\Contracts\FrontmatterNegative;
use Oru\Harness\Helpers\TestStringValue;

final readonly class AssertIsThrowableWithConstructor implements Assertion
{
    private StringValue $constructorString;

    private StringValue $nameString;

    public function __construct(
        private FrontmatterNegative $negative
    ) {
        $this->constructorString = new TestStringValue('constructor');
        $this->nameString = new TestStringValue('name');
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

        $exception = $actual->getValue();

        if (!$exception instanceof ObjectValue) {
            throw new AssertionFailedException(
                "`ThrowCompletion` does not contain an `ObjectValue`, got '{$exception}'",
                previous: $actual,
            );
        }

        try {
            $constructor = $exception->⟦Get⟧($this->constructorString, $exception);
        } catch (AbruptCompletion $throwable) {
            throw new EngineException(
                'Could not use `⟦Get⟧()` to retrieve `constructor`',
                previous: $throwable,
            );
        }

        if (!$constructor instanceof ObjectValue) {
            throw new AssertionFailedException(
                'Constructor value is not an `ObjectValue`',
                previous: $actual,
            );
        }

        try {
            $hasName = $constructor->⟦HasProperty⟧($this->nameString);
        } catch (AbruptCompletion $throwable) {
            throw new EngineException(
                'Could not use `⟦HasProperty⟧()` to check existence of `name`',
                previous: $throwable,
            );
        }

        if (!$hasName->bool) {
            throw new AssertionFailedException(
                'Constructor does not have a name',
                previous: $actual,
            );
        }

        try {
            $nameProperty = $constructor->⟦Get⟧($this->nameString, $constructor);
        } catch (AbruptCompletion $throwable) {
            throw new EngineException(
                'Could not use `⟦Get⟧()` to retrieve `constructor.name`',
                previous: $throwable,
            );
        }

        if ($this->negative->type() !== (string) $nameProperty) {
            throw new AssertionFailedException(
                "Expected `{$this->negative->type()}` but got `{$nameProperty}`",
                previous: $actual,
            );
        }
    }
}
