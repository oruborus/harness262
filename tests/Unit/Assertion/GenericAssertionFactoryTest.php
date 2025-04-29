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

namespace Tests\Unit\Assertion;

use Oru\EcmaScript\Core\Contracts\Agent;
use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\Harness\Assertion\AssertAsync;
use Oru\Harness\Assertion\AssertIsNormal;
use Oru\Harness\Assertion\AssertIsThrowableWithConstructor;
use Oru\Harness\Assertion\Exception\EngineException;
use Oru\Harness\Assertion\GenericAssertionFactory;
use Oru\Harness\Contracts\EngineFactory;
use Oru\Harness\Contracts\Frontmatter;
use Oru\Harness\Contracts\FrontmatterFlag;
use Oru\Harness\Contracts\FrontmatterNegative;
use Oru\Harness\Contracts\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

#[CoversClass(GenericAssertionFactory::class)]
final class GenericAssertionFactoryTest extends PHPUnitTestCase
{
    private function createEngineFactoryStub(bool $hasBoundValueFactory = true): EngineFactory
    {
        $agentStub = $this->createStub(Agent::class);
        if ($hasBoundValueFactory) {
            $agentStub->method('get')->willReturnCallback(fn($classString) => $this->createStub($classString));
        }

        $engineStub = $this->createConfiguredStub(Engine::class, [
            'getAgent' => $agentStub,
        ]);

        return $this->createConfiguredStub(EngineFactory::class, [
            'make' => $engineStub,
        ]);
    }

    #[Test]
    public function failsWhenAgentHasNoBoundValueFactory(): void
    {
        $this->expectException(EngineException::class);

        $factory = new GenericAssertionFactory($this->createEngineFactoryStub(hasBoundValueFactory: false));

        $factory->make($this->createStub(TestCase::class));
    }

    #[Test]
    public function returnsCorrectAssertionWithoutNegativeFrontmatter(): void
    {
        $factory = new GenericAssertionFactory($this->createEngineFactoryStub());

        $actual = $factory->make(
            $this->createConfiguredStub(TestCase::class, [
                'frontmatter' => $this->createConfiguredStub(Frontmatter::class, [
                    'negative' => null
                ])
            ])
        );

        $this->assertInstanceOf(AssertIsNormal::class, $actual);
    }

    #[Test]
    public function returnsCorrectAssertionWithNegativeFrontmatter(): void
    {
        $factory = new GenericAssertionFactory($this->createEngineFactoryStub());

        $actual = $factory->make(
            $this->createConfiguredStub(TestCase::class, [
                'frontmatter' => $this->createConfiguredStub(Frontmatter::class, [
                    'negative' => $this->createMock(FrontmatterNegative::class)
                ])
            ])
        );

        $this->assertInstanceOf(AssertIsThrowableWithConstructor::class, $actual);
    }

    #[Test]
    public function returnsCorrectAssertionWithAsyncFrontmatterFlag(): void
    {
        $factory = new GenericAssertionFactory($this->createEngineFactoryStub());

        $actual = $factory->make(
            $this->createConfiguredStub(TestCase::class, [
                'frontmatter' => $this->createConfiguredStub(Frontmatter::class, [
                    'flags' => [FrontmatterFlag::async]
                ])
            ])
        );

        $this->assertInstanceOf(AssertAsync::class, $actual);
    }
}
