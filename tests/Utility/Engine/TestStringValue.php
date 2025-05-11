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

use Oru\EcmaScript\Core\Contracts\Values\NumberValue;
use Oru\EcmaScript\Core\Contracts\Values\StringValue;
use Stringable;

use function is_array;

final class TestStringValue implements StringValue
{
    private string $value;

    public function __construct(
        array|string|Stringable $value,
    ) {
        if (is_array($value)) {
            throw new \RuntimeException('`TestValueFactory::createString()` with array input is not implemented');
        }

        $this->value = (string) $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function codeUnits(): array
    {
        throw new \RuntimeException('`TestStringValue::codeUnits()` is not implemented');
    }

    public function codePoints(): array
    {
        throw new \RuntimeException('`TestStringValue::codePoints()` is not implemented');
    }

    public function getLength(): int
    {
        throw new \RuntimeException('`TestStringValue::getLength()` is not implemented');
    }

    public function ord(): int
    {
        throw new \RuntimeException('`TestStringValue::ord()` is not implemented');
    }

    public function substr(NumberValue $start, NumberValue $length): StringValue
    {
        throw new \RuntimeException('`TestStringValue::substr()` is not implemented');
    }

    public function stringIndexOf(StringValue $searchValue, NumberValue $fromIndex): NumberValue
    {
        throw new \RuntimeException('`TestStringValue::stringIndexOf()` is not implemented');
    }

    public function codePointAt(int $index): int
    {
        throw new \RuntimeException('`TestStringValue::codePointAt()` is not implemented');
    }

    public function append(int|array|string|StringValue ...$values): static
    {
        throw new \RuntimeException('`TestStringValue::append()` is not implemented');
    }

    public function to16BitBMPCodePoints(): array
    {
        throw new \RuntimeException('`TestStringValue::to16BitBMPCodePoints()` is not implemented');
    }
}
