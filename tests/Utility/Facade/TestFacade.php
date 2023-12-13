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

namespace Tests\Utility\Facade;

use Exception;
use Oru\Harness\Contracts\Facade;

use function array_filter;
use function strpos;

class TestFacade implements Facade
{
    private bool $fails = false;

    private bool $errors = false;

    public function initialize(): void {}

    public function path(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'create-test-facade.php';
    }

    public function completionGetValue(mixed $completion): mixed
    {
        return null;
    }

    public function isNormalCompletion(mixed $value): bool
    {
        return !$this->fails && !$this->errors;
    }

    public function isThrowCompletion(mixed $value): bool
    {
        return $this->fails || $this->errors;
    }

    public function isObject(mixed $value): bool
    {
        return true;
    }

    public function objectGetAsString(mixed $object, string $propertyKey): ?string
    {
        return '';
    }

    public function objectGet(mixed $object, string $propertyKey): mixed
    {
        return null;
    }

    public function objectHasProperty(mixed $object, string $propertyKey): bool
    {
        return true;
    }

    public function toString(mixed $value): string
    {
        return '';
    }

    public function engineSupportedFeatures(): array
    {
        return [];
    }

    public function engineAddFiles(string ...$paths): void
    {
        $this->fails = !array_filter($paths, static fn(string $path): bool => strpos($path, 'fail') !== false);
        $this->errors = !array_filter($paths, static fn(string $path): bool => strpos($path, 'error') !== false);
    }

    public function engineAddCode(string $source, ?string $file = null, bool $isModuleCode = false): void
    {
        $this->fails = strpos($source, 'fail') !== false;
        $this->errors = strpos($source, 'error') !== false;
    }

    public function engineRun(): mixed
    {
        if ($this->errors) {
            throw new Exception('Planned error');
        }
        return null;
    }
}
