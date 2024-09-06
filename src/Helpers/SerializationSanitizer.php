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

use ReflectionClass;
use Throwable;
use UnitEnum;

use function array_key_exists;
use function gettype;
use function is_null;
use function serialize;
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
        if (array_key_exists($id, $this->checkedObjectIds)) {
            /** @var ?T */
            return $this->checkedObjectIds[$id];
        }

        return $this->sanitizeObject($value, $id);
    }

    /**
     * @template T of object
     * @param T $value
     * @return ?T
     */
    private function sanitizeObject(object $value, int $id): ?object
    {
        if ($value instanceof UnitEnum) {
            $this->checkedObjectIds[$id] = $value;
            return $value;
        }

        $reflectionClass = new ReflectionClass($value);

        if ($reflectionClass->isAnonymous()) {
            $this->checkedObjectIds[$id] = null;
            return null;
        }

        try {
            $newInstance = $reflectionClass->newInstanceWithoutConstructor();
        } catch (Throwable) {
            $this->checkedObjectIds[$id] = null;
            return null;
        }

        try {
            serialize($newInstance);
        } catch (Throwable) {
            $this->checkedObjectIds[$id] = null;
            return null;
        }

        $this->checkedObjectIds[$id] = $newInstance;
        do {
            /** @psalm-suppress MixedAssignment */
            foreach ($reflectionClass->getProperties() as $property) {
                if (!$property->isInitialized($value)) {
                    continue;
                }

                $newPropertyValue = $this->sanitizeValue($property->getValue($value));
                if (!is_null($newPropertyValue) || ($property->getType()?->allowsNull() ?? false)) {
                    $property->setValue($newInstance, $newPropertyValue);
                }
            }
        } while ($reflectionClass = $reflectionClass->getParentClass());

        /** @var T */
        return $newInstance;
    }
};
