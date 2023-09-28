<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Assertion;

use Oru\EcmaScript\Harness\Assertion\AssertIsNormal;
use Oru\EcmaScript\Harness\Assertion\AssertIsThrowableWithConstructor;
use Oru\EcmaScript\Harness\Assertion\GenericAssertionFactory;
use Oru\EcmaScript\Harness\Contracts\Facade;
use Oru\EcmaScript\Harness\Contracts\Frontmatter;
use Oru\EcmaScript\Harness\Contracts\FrontmatterNegative;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
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
}
