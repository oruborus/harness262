<?php

/**
 * Copyright (c) 2024, Felix Jahn
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

use Closure;
use ReflectionClass;
use Reflector;
use UnitEnum;

use function array_key_exists;
use function gettype;
use function spl_object_id;

final class SerializationSanitizer
{
    /** @var array<int, ?object> $checkedObjectIds */
    private array $checkedObjectIds = [];

    /** 
     * @template T
     * @param T $value
     * @return T
     */
    public function sanitize(mixed $value): mixed
    {
        $this->checkedObjectIds = [];

        return $this->sanitizeValue($value);
    }

    /** 
     * @template T
     * @param T $value
     * @return T
     */
    private function sanitizeValue(mixed $value): mixed
    {
        return match (gettype($value)) {
            'array' => $this->sanitizeArray($value),
            'object' => $this->sanitizeObjectCached($value),
            default => $value,
        };
    }

    /**
     * @template T of array
     * @param T $value
     * @return T
     */
    private function sanitizeArray(array $value): array
    {
        /** @psalm-suppress MixedAssignment */
        foreach ($value as &$element) {
            $element = $this->sanitizeValue($element);
        }

        /** @var T */
        return $value;
    }

    /**
     * @template T of object
     * @param T $value
     * @return ?T
     */
    private function sanitizeObjectCached(object $value): ?object
    {
        $id = spl_object_id($value);
        if (!array_key_exists($id, $this->checkedObjectIds)) {
            $this->checkedObjectIds[$id] = $value;
            $this->checkedObjectIds[$id] = $this->sanitizeObject($value);
        }

        /** @var ?T */
        return $this->checkedObjectIds[$id];
    }

    /**
     * @template T of object
     * @param T $value
     * @return ?T
     */
    private function sanitizeObject(object $value): ?object
    {
        if ($value instanceof UnitEnum) {
            return $value;
        }

        if ($value instanceof Closure) {
            return null;
        }

        if ($value instanceof Reflector) {
            return null;
        }

        $reflectionClass = new ReflectionClass($value);

        if ($reflectionClass->isAnonymous()) {
            return null;
        }

        $newInstance = $reflectionClass->newInstanceWithoutConstructor();
        do {
            /** @psalm-suppress MixedAssignment */
            foreach ($reflectionClass->getProperties() as $property) {
                $newPropertyValue = $this->sanitizeValue($property->getValue($value));
                $property->setValue($newInstance, $newPropertyValue);
            }
        } while ($reflectionClass = $reflectionClass->getParentClass());

        /** @var T */
        return $newInstance;
    }
};
