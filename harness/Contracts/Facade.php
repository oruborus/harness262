<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface Facade
{
    public static function path(): string;

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
