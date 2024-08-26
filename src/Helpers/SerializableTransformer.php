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
use Reflector;
use Throwable;

use function is_object;
use function serialize;

final class SerializableTransformer
{
    /** 
     * @template T
     * @param T $value
     * @return T
     */
    public function transform(mixed $value): mixed
    {
        try {
            serialize($value);
        } catch (Throwable $th) {
            if (
                !is_object($value)
                || $value instanceof Reflector
            ) {
                throw $th;
            }

            $rc = new ReflectionClass($value);

            if ($rc->isAnonymous()) {
                throw $th;
            }

            $in = $rc->newInstanceWithoutConstructor();
            foreach ($rc->getProperties() as $property) {
                try {
                    $newPropertyValue = $this->transform($property->getValue($value));
                    $property->setValue($in, $newPropertyValue);
                } catch (Throwable) {
                }
            }

            $value = $in;
        }

        /** @var T */
        return $value;
    }
};
