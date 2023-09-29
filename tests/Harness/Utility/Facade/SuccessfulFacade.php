<?php

declare(strict_types=1);

namespace Tests\Harness\Utility\Facade;

use Oru\EcmaScript\Harness\Contracts\Facade;

final class SuccessfulFacade implements Facade
{
    public function completionGetValue(mixed $completion): mixed
    {
        return null;
    }

    public function isNormalCompletion(mixed $value): bool
    {
        return true;
    }

    public function isThrowCompletion(mixed $value): bool
    {
        return false;
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
    }

    public function engineAddCode(string $source, ?string $file = null, bool $isModuleCode = false): void
    {
    }

    public function engineRun(): mixed
    {
        return null;
    }
}
