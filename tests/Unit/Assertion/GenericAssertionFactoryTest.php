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

namespace Tests\Unit\Assertion;

use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\Harness\Assertion\AssertAsync;
use Oru\Harness\Assertion\AssertIsNormal;
use Oru\Harness\Assertion\AssertIsThrowableWithConstructor;
use Oru\Harness\Assertion\AssertMultiple;
use Oru\Harness\Assertion\GenericAssertionFactory;
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
    #[Test]
    public function returnsCorrectAssertionWithoutNegativeFrontmatter(): void
    {
        $factory = new GenericAssertionFactory($this->createStub(Engine::class));

        $actual = $factory->make(
            $this->createConfiguredMock(TestCase::class, [
                'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                    'negative' => null
                ])
            ])
        );

        $this->assertInstanceOf(AssertIsNormal::class, $actual);
    }

    #[Test]
    public function returnsCorrectAssertionWithNegativeFrontmatter(): void
    {
        $factory = new GenericAssertionFactory($this->createStub(Engine::class));

        $actual = $factory->make(
            $this->createConfiguredMock(TestCase::class, [
                'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                    'negative' => $this->createMock(FrontmatterNegative::class)
                ])
            ])
        );

        $this->assertInstanceOf(AssertIsThrowableWithConstructor::class, $actual);
    }

    #[Test]
    public function returnsCorrectAssertionWithAsyncFrontmatterFlag(): void
    {
        $factory = new GenericAssertionFactory($this->createStub(Engine::class));

        $actual = $factory->make(
            $this->createConfiguredMock(TestCase::class, [
                'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                    'flags' => [FrontmatterFlag::async]
                ])
            ])
        );

        $this->assertInstanceOf(AssertMultiple::class, $actual);
        /** @var AssertMultiple $actual */
        $this->assertInstanceOf(AssertIsNormal::class, $actual->assertions()[0]);
        $this->assertInstanceOf(AssertAsync::class, $actual->assertions()[1]);
    }
}
