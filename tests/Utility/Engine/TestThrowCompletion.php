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

namespace Tests\Utility\Engine;

use Exception;
use Oru\EcmaScript\Core\Contracts\Values\LanguageValue;
use Oru\EcmaScript\Core\Contracts\Values\EmptyValue;
use Oru\EcmaScript\Core\Contracts\Values\StringValue;
use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
use ReflectionClass;

final class TestThrowCompletion extends Exception implements ThrowCompletion
{
    private ?ReflectionClass $reflectionClass = null;

    public function __construct(
        bool $unserializable,
    ) {
        if ($unserializable === true) {
            $this->reflectionClass = new ReflectionClass($this);
        }
    }

    public function getValue(): LanguageValue
    {
        return new TestStringValue('Planned failure');
    }

    public function getTarget(): StringValue|EmptyValue
    {
        throw new \RuntimeException('`ThrowCompletion::getTarget()` should not be called');
    }
}
