<?php

/**
 * Copyright (c) 2023-2025, Felix Jahn
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
use Oru\EcmaScript\Core\Contracts\Values\UnusedValue;
use Oru\EcmaScript\Core\Contracts\Values\ValueFactory;
use Tests\Utility\Engine\Exception\ObjectIdExtractionException;
use Tests\Utility\Engine\Exception\PidExtractionException;

use function array_filter;
use function preg_match;
use function strpos;
use function usleep;

final class TestEngine implements Engine
{
    public function __construct(
        private Agent $agent = new TestAgent(),
        private ValueFactory $valueFactory = new TestValueFactory(),
    ) {
        $this->agent->bind(ValueFactory::class, $this->valueFactory);
    }

    private string $print = '';

    private bool $throws = false;

    private bool $fails = false;

    private bool $unserializable = false;

    private bool $errors = false;

    private bool $emitsPid = false;

    private bool $emitsObjectId = false;

    private int $runsFor = 0;

    public function container(): Container
    {
        throw new \RuntimeException('`TestEngine::container()` is not implemented');
    }

    public function getAgent(): Agent
    {
        return $this->agent;
    }

    public function addFiles(string ...$paths): void
    {
        $this->fails = !array_filter($paths, static fn(string $path): bool => strpos($path, 'fail') !== false);
        $this->errors = !array_filter($paths, static fn(string $path): bool => strpos($path, 'error') !== false);
    }

    public function addCode(string $source, ?string $file = null, bool $isModuleCode = false): void
    {
        if (preg_match('/print\(\'(?<print>.+)\'\)/', $source, $matches) === 1) {
            $this->print = $matches['print'];
        }
        $this->fails = strpos($source, 'fail') !== false;
        $this->throws = strpos($source, 'throw') !== false;
        $this->unserializable = strpos($source, 'unserializable') !== false;
        $this->errors = strpos($source, 'error') !== false;
        $this->emitsPid = strpos($source, 'pid') !== false;
        $this->emitsObjectId = strpos($source, 'oid') !== false;
        if (preg_match('/run for (?<timeout>\d+) seconds?/', $source, $matches) === 1) {
            $this->runsFor = (int) $matches['timeout'];
        }
    }

    public function addJob(callable $job): void
    {
        throw new \RuntimeException('`TestEngine::addJob()` is not implemented');
    }

    public function run(): LanguageValue|AbruptCompletion
    {
        if ($this->print !== '') {
            echo $this->print;
            if ($this->throws) {
                return new TestThrowCompletion($this->unserializable);
            }

            return $this->createUnused();
        }

        if ($this->errors) {
            throw new Exception('Planned error');
        }

        if ($this->fails) {
            return new TestThrowCompletion($this->unserializable);
        }

        if ($this->emitsPid) {
            throw new PidExtractionException();
        }

        if ($this->emitsObjectId) {
            throw new ObjectIdExtractionException();
        }

        if ($this->runsFor > 0) {
            usleep($this->runsFor * 1_000_000);
        }

        return $this->createUnused();
    }

    private function createUnused(): UnusedValue
    {
        return new class implements UnusedValue
        {
            public function getValue(): never
            {
                throw new \RuntimeException('`UnusedValue::getValue()` should not be called');
            }
        };
    }

    public function hostDefinedProperty(string $key): mixed
    {
        return null;
    }

    /** @return array<string, mixed> */
    public function hostDefinedProperties(): array
    {
        return [];
    }

    public function getSupportedFeatures(): array
    {
        return [];
    }
}
