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

namespace Tests\Unit\TestCase;

use Generator;
use Oru\Harness\Contracts\FrontmatterFlag;
use Oru\Harness\Contracts\ImplicitStrictness;
use Oru\Harness\Contracts\Storage;
use Oru\Harness\Contracts\TestSuite;
use Oru\Harness\Frontmatter\GenericFrontmatter;
use Oru\Harness\TestCase\Exception\MissingFrontmatterException;
use Oru\Harness\TestCase\GenericTestCase;
use Oru\Harness\TestCase\GenericTestCaseFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function in_array;
use function array_shift;

#[CoversClass(GenericTestCaseFactory::class)]
#[UsesClass(GenericFrontmatter::class)]
#[UsesClass(GenericTestCase::class)]
final class GenericTestCaseFactoryTest extends TestCase
{
    #[Test]
    public function failsWhenProvidedFileCannotBeRead(): void
    {
        $path = 'xxx';
        $this->expectExceptionMessage("Could not open `{$path}`");

        $factory = new GenericTestCaseFactory(
            $this->createMock(Storage::class),
            $this->createStub(TestSuite::class)
        );

        $factory->make($path);
    }

    #[Test]
    public function failsOnMissingFrontmatter(): void
    {
        $this->expectExceptionObject(new MissingFrontmatterException('Provided test file does not contain a frontmatter section'));

        $factory = new GenericTestCaseFactory(
            $this->createConfiguredMock(Storage::class, ['get' => '']),
            $this->createStub(TestSuite::class)
        );

        $factory->make('content');
    }

    #[Test]
    #[dataProvider('provideNonStrictFlags')]
    public function createsNonStrictTestConfiguration(FrontmatterFlag $flag): void
    {
        $expected = "/*---\ndescription: required\nflags: [{$flag->value}]\n---*/\n// CONTENT";
        $factory = new GenericTestCaseFactory(
            $this->createConfiguredMock(Storage::class, ['get' => $expected]),
            $this->createStub(TestSuite::class)
        );

        $actual = $factory->make('content');

        $this->assertCount(1, $actual);
        $this->assertSame($expected, array_shift($actual)->content());
    }

    /**
     * @return Generator<string, array{0: FrontmatterFlag}>
     */
    public static function provideNonStrictFlags(): Generator
    {
        yield 'raw' => [FrontmatterFlag::raw];
        yield 'module' => [FrontmatterFlag::module];
        yield 'noStrict' => [FrontmatterFlag::noStrict];
    }

    #[Test]
    #[dataProvider('provideStrictFlags')]
    public function createsStrictTestConfiguration(FrontmatterFlag $flag): void
    {
        $expected = "/*---\ndescription: required\nflags: [{$flag->value}]\n---*/\n// CONTENT";
        $factory = new GenericTestCaseFactory(
            $this->createConfiguredMock(Storage::class, ['get' => $expected]),
            $this->createStub(TestSuite::class)
        );

        $actual = $factory->make('content');

        $this->assertCount(1, $actual);
        $this->assertSame("\"use strict\";\n" . $expected, array_shift($actual)->content());
    }

    /**
     * @return Generator<string, array{0: FrontmatterFlag}>
     */
    public static function provideStrictFlags(): Generator
    {
        yield 'onlyStrict' => [FrontmatterFlag::onlyStrict];
    }

    #[Test]
    #[dataProvider('provideOtherFlags')]
    public function createsStrictAndNonStrictTestConfigurations(FrontmatterFlag $flag): void
    {
        $expected = "/*---\ndescription: required\nflags: [{$flag->value}]\n---*/\n// CONTENT";
        $factory = new GenericTestCaseFactory(
            $this->createConfiguredMock(Storage::class, ['get' => $expected]),
            $this->createStub(TestSuite::class)
        );

        $actual = $factory->make('content');

        $this->assertCount(2, $actual);
        [$first, $second] = $actual;
        $this->assertSame($expected, $first->content());
        $this->assertSame($first->implicitStrictness(), ImplicitStrictness::Loose);
        $this->assertSame("\"use strict\";\n" . $expected, $second->content());
        $this->assertSame($second->implicitStrictness(), ImplicitStrictness::Strict);
    }

    /**
     * @return Generator<string, array{0: FrontmatterFlag}>
     */
    public static function provideOtherFlags(): Generator
    {
        $strictnessChangingFlags = [];

        foreach (static::provideNonStrictFlags() as $wrappedFlag) {
            $strictnessChangingFlags = [...$strictnessChangingFlags, ...$wrappedFlag];
        }
        foreach (static::provideStrictFlags() as $wrappedFlag) {
            $strictnessChangingFlags = [...$strictnessChangingFlags, ...$wrappedFlag];
        }

        foreach (FrontmatterFlag::cases() as $flag) {
            if (!in_array($flag, $strictnessChangingFlags, true)) {
                yield $flag->value => [$flag];
            }
        }
    }

    #[Test]
    public function emptyLinesAreIgnored(): void
    {
        $frontmatter = "description: required\nflags: [noStrict]\n\n\nincludes: [doneprintHandle.js]";
        $expected = "/*---\n{$frontmatter}\n---*/";
        $factory = new GenericTestCaseFactory(
            $this->createConfiguredMock(Storage::class, ['get' => $expected]),
            $this->createStub(TestSuite::class)
        );

        $actual = $factory->make('content');

        $this->assertCount(1, $actual);
        $this->assertSame($expected, array_shift($actual)->content());
    }

    #[Test]
    public function indentationIsHandled(): void
    {
        $expected = "/*---\n   description: required\n   flags: [noStrict]\n   includes: [doneprintHandle.js]\n---*/";
        $factory = new GenericTestCaseFactory(
            $this->createConfiguredMock(Storage::class, ['get' => $expected]),
            $this->createStub(TestSuite::class)
        );

        $actual = $factory->make('content');

        $this->assertCount(1, $actual);
        $this->assertSame($expected, array_shift($actual)->content());
    }

    #[Test]
    public function canHandleMultipleInputPaths(): void
    {
        $factory = new GenericTestCaseFactory(
            $this->createConfiguredMock(Storage::class, [
                'get' => "/*---\ndescription: required\nflags: [raw]\n---*/\n// CONTENT"]),
            $this->createStub(TestSuite::class)
        );

        $actual = $factory->make('path1', 'path2', 'path3');

        $this->assertCount(3, $actual);
    }
}
