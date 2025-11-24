<?php

/**
 * Copyright (c) 2025, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Oru\Harness\Helpers;

use Oru\EcmaScript\Core\Contracts\Values\NumberValue;
use Oru\EcmaScript\Core\Contracts\Values\StringValue;
use Oru\Harness\Helpers\Exception\InvalidMethodCallException;

final readonly class TestStringValue implements StringValue
{
    public function __construct(
        private string $value,
    ) {}

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
        throw new InvalidMethodCallException('UNREACHABLE');
    }

    public function codePoints(): array
    {
        throw new InvalidMethodCallException('UNREACHABLE');
    }

    public function getLength(): int
    {
        throw new InvalidMethodCallException('UNREACHABLE');
    }

    public function ord(): int
    {
        throw new InvalidMethodCallException('UNREACHABLE');
    }

    public function substr(NumberValue $start, NumberValue $length): StringValue
    {
        throw new InvalidMethodCallException('UNREACHABLE');
    }

    public function stringIndexOf(StringValue $searchValue, NumberValue $fromIndex): NumberValue
    {
        throw new InvalidMethodCallException('UNREACHABLE');
    }

    public function codePointAt(int $index): int
    {
        throw new InvalidMethodCallException('UNREACHABLE');
    }

    public function append(int|array|string|StringValue ...$values): static
    {
        throw new InvalidMethodCallException('UNREACHABLE');
    }

    public function to16BitBMPCodePoints(): array
    {
        throw new InvalidMethodCallException('UNREACHABLE');
    }
}
