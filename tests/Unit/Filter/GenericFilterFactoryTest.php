<?php

/**
 * Copyright (c) 2023, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Tests\Unit\Filter;

use Generator;
use Oru\Harness\Contracts\ArgumentsParser;
use Oru\Harness\Filter\CompositeFilter;
use Oru\Harness\Filter\FileNameDoesNotMatchRegExpFilter;
use Oru\Harness\Filter\FileNameMatchesRegExpFilter;
use Oru\Harness\Filter\FrontmatterFlagFilter;
use Oru\Harness\Filter\GenericFilterFactory;
use Oru\Harness\Filter\ImplicitStrictFilter;
use Oru\Harness\Filter\PassthroughFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericFilterFactory::class)]
final class GenericFilterFactoryTest extends TestCase
{
    #[Test]
    public function createsPassthroughFilterWhenNoOptionIsProvided(): void
    {
        $argumentsParserStub = $this->createStub(ArgumentsParser::class);

        $factory = new GenericFilterFactory($argumentsParserStub);
        $actual = $factory->make();

        $this->assertInstanceOf(PassthroughFilter::class, $actual);
    }

    #[Test]
    public function createsFileNameDoesNotMatchRegExpFilterWhenIncludeOptionIsProvided(): void
    {
        $argumentsParserStub = $this->createStub(ArgumentsParser::class);
        $argumentsParserStub->method('hasOption')->willReturnCallback(static fn(string $option): bool => $option === 'include');

        $factory = new GenericFilterFactory($argumentsParserStub);
        $actual = $factory->make();

        $this->assertInstanceOf(FileNameMatchesRegExpFilter::class, $actual);
    }

    #[Test]
    public function createsFileNameMatchesRegExpFilterWhenExcludeOptionIsProvided(): void
    {
        $argumentsParserStub = $this->createStub(ArgumentsParser::class);
        $argumentsParserStub->method('hasOption')->willReturnCallback(static fn(string $option): bool => $option === 'exclude');

        $factory = new GenericFilterFactory($argumentsParserStub);
        $actual = $factory->make();

        $this->assertInstanceOf(FileNameDoesNotMatchRegExpFilter::class, $actual);
    }

    #[Test]
    #[DataProvider('provideFrontmatterOption')]
    public function createsFrontmatterFlagFilterWhenFrontmatterOptionIsProvided(string $input): void
    {
        $argumentsParserStub = $this->createStub(ArgumentsParser::class);
        $argumentsParserStub->method('hasOption')->willReturnCallback(static fn(string $option): bool => $option === $input);

        $factory = new GenericFilterFactory($argumentsParserStub);
        $actual = $factory->make();

        $this->assertInstanceOf(FrontmatterFlagFilter::class, $actual);
    }

    public static function provideFrontmatterOption(): Generator
    {
        yield 'only-strict' => ['only-strict'];
        yield 'no-strict'   => ['no-strict'];
        yield 'module'      => ['module'];
        yield 'async'       => ['async'];
        yield 'raw'         => ['raw'];
    }

    #[Test]
    #[DataProvider('provideStrictnessOption')]
    public function createsImplicitStrictFilterWhenStrictnessOptionIsProvided(string $input): void
    {
        $argumentsParserStub = $this->createStub(ArgumentsParser::class);
        $argumentsParserStub->method('hasOption')->willReturnCallback(static fn(string $option): bool => $option === $input);

        $factory = new GenericFilterFactory($argumentsParserStub);
        $actual = $factory->make();

        $this->assertInstanceOf(ImplicitStrictFilter::class, $actual);
    }

    public static function provideStrictnessOption(): Generator
    {
        yield 'strict' => ['strict'];
        yield 'loose'  => ['loose'];
    }

    #[Test]
    public function createsCompositeFilterWhenMultipleOptionsAreProvided(): void
    {
        $argumentsParserStub = $this->createStub(ArgumentsParser::class);
        $argumentsParserStub->method('hasOption')->willReturnCallback(static fn(): bool => true);

        $factory = new GenericFilterFactory($argumentsParserStub);
        $actual = $factory->make();

        $this->assertInstanceOf(CompositeFilter::class, $actual);
    }
}
