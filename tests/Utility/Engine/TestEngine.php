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

use Exception;
use Oru\EcmaScript\Core\Contracts\Container;
use Oru\EcmaScript\Core\Contracts\Agent;
use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\EcmaScript\Core\Contracts\Values\LanguageValue;
use Oru\EcmaScript\Core\Contracts\Values\AbruptCompletion;
use Oru\EcmaScript\Core\Contracts\Values\EmptyValue;
use Oru\EcmaScript\Core\Contracts\Values\StringValue;
use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
use Oru\EcmaScript\Core\Contracts\Values\UnusedValue;

use function array_filter;
use function strpos;

final class TestEngine implements Engine
{
    private bool $fails = false;

    private bool $errors = false;

    public function container(): Container
    {
        throw new \RuntimeException('`TestEngine::container()` is not implemented');
    }

    public function getAgent(): Agent
    {
        return new TestAgent();
    }

    public function addFiles(string ...$paths): void
    {
        $this->fails = !array_filter($paths, static fn (string $path): bool => strpos($path, 'fail') !== false);
        $this->errors = !array_filter($paths, static fn (string $path): bool => strpos($path, 'error') !== false);
    }

    public function addCode(string $source, ?string $file = null, bool $isModuleCode = false): void
    {
        $this->fails = strpos($source, 'fail') !== false;
        $this->errors = strpos($source, 'error') !== false;
    }

    public function addJob(callable $job): void
    {
        throw new \RuntimeException('`TestEngine::addJob()` is not implemented');
    }

    public function run(): LanguageValue|AbruptCompletion
    {
        if ($this->errors) {
            throw new Exception('Planned error');
        }

        if ($this->fails) {
            return new class implements ThrowCompletion
            {
                public function getValue(): LanguageValue
                {
                    return new TestStringValue('Planned failure');
                }

                public function getTarget(): StringValue|EmptyValue
                {
                    throw new \RuntimeException('`ThrowCompletion::getTarget()` should not be called');
                }
            };
        }

        return new class implements UnusedValue
        {
            public function getValue(): never
            {
                throw new \RuntimeException('`UnusedValue::getValue()` should not be called');
            }
        };
    }

    public function getSupportedFeatures(): array
    {
        return [];
    }
}
