<?php

declare(strict_types=1);

namespace Tests\Unit\Assertion;

use Oru\Harness\Assertion\AssertAsync;
use Oru\Harness\Assertion\AssertIsNormal;
use Oru\Harness\Assertion\AssertIsThrowableWithConstructor;
use Oru\Harness\Assertion\GenericAssertionFactory;
use Oru\Harness\Contracts\Facade;
use Oru\Harness\Contracts\Frontmatter;
use Oru\Harness\Contracts\FrontmatterFlag;
use Oru\Harness\Contracts\FrontmatterNegative;
use Oru\Harness\Contracts\TestConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericAssertionFactory::class)]
#[UsesClass(AssertIsNormal::class)]
#[UsesClass(AssertIsThrowableWithConstructor::class)]
final class GenericAssertionFactoryTest extends TestCase
{
    #[Test]
    public function returnsCorrectAssertionWithoutNegativeFrontmatter(): void
    {
        $factory = new GenericAssertionFactory($this->createMock(Facade::class));

        $actual = $factory->make(
            $this->createConfiguredMock(TestConfig::class, [
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
        $factory = new GenericAssertionFactory($this->createMock(Facade::class));

        $actual = $factory->make(
            $this->createConfiguredMock(TestConfig::class, [
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
        $factory = new GenericAssertionFactory($this->createMock(Facade::class));

        $actual = $factory->make(
            $this->createConfiguredMock(TestConfig::class, [
                'frontmatter' => $this->createConfiguredMock(Frontmatter::class, [
                    'flags' => [FrontmatterFlag::async]
                ])
            ])
        );

        $this->assertInstanceOf(AssertAsync::class, $actual);
    }
}
