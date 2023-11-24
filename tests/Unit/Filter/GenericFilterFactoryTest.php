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

use Oru\Harness\Contracts\ArgumentsParser;
use Oru\Harness\Filter\CompositeFilter;
use Oru\Harness\Filter\FileNameDoesNotMatchRegExpFilter;
use Oru\Harness\Filter\FileNameMatchesRegExpFilter;
use Oru\Harness\Filter\GenericFilterFactory;
use Oru\Harness\Filter\PassthroughFilter;
use PHPUnit\Framework\Attributes\CoversClass;
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
        $argumentsParserStub->method('hasOption')->willReturnMap([['include', true], ['exclude', false]]);

        $factory = new GenericFilterFactory($argumentsParserStub);
        $actual = $factory->make();

        $this->assertInstanceOf(FileNameDoesNotMatchRegExpFilter::class, $actual);
    }

    #[Test]
    public function createsFileNameMatchesRegExpFilterWhenExcludeOptionIsProvided(): void
    {
        $argumentsParserStub = $this->createStub(ArgumentsParser::class);
        $argumentsParserStub->method('hasOption')->willReturnMap([['include', false], ['exclude', true]]);

        $factory = new GenericFilterFactory($argumentsParserStub);
        $actual = $factory->make();

        $this->assertInstanceOf(FileNameMatchesRegExpFilter::class, $actual);
    }

    #[Test]
    public function createsCompositeFilterWhenMultipleOptionsAreProvided(): void
    {
        $argumentsParserStub = $this->createStub(ArgumentsParser::class);
        $argumentsParserStub->method('hasOption')->willReturnMap([['include', true], ['exclude', true]]);

        $factory = new GenericFilterFactory($argumentsParserStub);
        $actual = $factory->make();

        $this->assertInstanceOf(CompositeFilter::class, $actual);
    }
}
