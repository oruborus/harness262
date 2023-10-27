<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Generator;
use Oru\Harness\Config\Exception\MissingFrontmatterException;
use Oru\Harness\Config\GenericTestConfig;
use Oru\Harness\Config\GenericTestConfigFactory;
use Oru\Harness\Contracts\FrontmatterFlag;
use Oru\Harness\Contracts\Storage;
use Oru\Harness\Frontmatter\GenericFrontmatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function in_array;
use function array_shift;

#[CoversClass(GenericTestConfigFactory::class)]
#[UsesClass(GenericFrontmatter::class)]
#[UsesClass(GenericTestConfig::class)]
final class GenericTestConfigFactoryTest extends TestCase
{
    #[Test]
    public function failsWhenProvidedFileCannotBeRead(): void
    {
        $path = 'xxx';
        $this->expectExceptionMessage("Could not open `{$path}`");

        $factory = new GenericTestConfigFactory($this->createMock(Storage::class));

        $factory->make($path);
    }

    #[Test]
    public function failsOnMissingFrontmatter(): void
    {
        $this->expectExceptionObject(new MissingFrontmatterException('Provided test file does not contain a frontmatter section'));

        $factory = new GenericTestConfigFactory($this->createConfiguredMock(Storage::class, ['get' => '']));

        $factory->make('content');
    }

    #[Test]
    #[dataProvider('provideNonStrictFlags')]
    public function createsNonStrictTestConfiguration(FrontmatterFlag $flag): void
    {
        $expected = "/*---\ndescription: required\nflags: [{$flag->value}]\n---*/\n// CONTENT";
        $factory = new GenericTestConfigFactory($this->createConfiguredMock(Storage::class, ['get' => $expected]));

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
        $factory = new GenericTestConfigFactory($this->createConfiguredMock(Storage::class, ['get' => $expected]));

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
        $factory = new GenericTestConfigFactory($this->createConfiguredMock(Storage::class, ['get' => $expected]));

        $actual = $factory->make('content');

        $this->assertCount(2, $actual);
        $this->assertSame($expected, array_shift($actual)->content());
        $this->assertSame("\"use strict\";\n" . $expected, array_shift($actual)->content());
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
        $factory = new GenericTestConfigFactory($this->createConfiguredMock(Storage::class, ['get' => $expected]));

        $actual = $factory->make('content');

        $this->assertCount(1, $actual);
        $this->assertSame($expected, array_shift($actual)->content());
    }

    #[Test]
    public function indentationIsHandled(): void
    {
        $expected = "/*---\n   description: required\n   flags: [noStrict]\n   includes: [doneprintHandle.js]\n---*/";
        $factory = new GenericTestConfigFactory($this->createConfiguredMock(Storage::class, ['get' => $expected]));

        $actual = $factory->make('content');

        $this->assertCount(1, $actual);
        $this->assertSame($expected, array_shift($actual)->content());
    }
}
