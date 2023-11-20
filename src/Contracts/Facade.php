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

namespace Oru\Harness\Contracts;

interface Facade
{
    public function initialize(): void;

    public function path(): string;

    public function completionGetValue(mixed $completion): mixed;

    public function isNormalCompletion(mixed $value): bool;

    public function isThrowCompletion(mixed $value): bool;

    public function isObject(mixed $value): bool;

    public function objectGetAsString(mixed $object, string $propertyKey): ?string;

    public function objectGet(mixed $object, string $propertyKey): mixed;

    public function objectHasProperty(mixed $object, string $propertyKey): bool;

    public function toString(mixed $value): string;

    /**
     * @return array<int, string>
     */
    public function engineSupportedFeatures(): array;

    public function engineAddFiles(string ...$paths): void;

    public function engineAddCode(string $source, ?string $file = null, bool $isModuleCode = false): void;

    public function engineRun(): mixed;
}
