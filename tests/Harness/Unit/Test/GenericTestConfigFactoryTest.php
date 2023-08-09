<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Test;

use Generator;
use Oru\EcmaScript\Harness\Contracts\Storage;
use Oru\EcmaScript\Harness\Contracts\TestConfigFlag;
use Oru\EcmaScript\Harness\Contracts\TestConfigInclude;
use Oru\EcmaScript\Harness\Test\GenericTestConfig;
use Oru\EcmaScript\Harness\Test\GenericTestConfigFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function array_map;
use function implode;

#[CoversClass(GenericTestConfigFactory::class)]
final class GenericTestConfigFactoryTest extends TestCase
{
    private function getStorage(array $predefined = []): Storage
    {
        return new class($predefined) implements Storage
        {
            public function __construct(
                private array $storage
            ) {
            }

            public function put(string $key, mixed $content): void
            {
                $this->storage[$key] = $content;
            }

            public function get(string $key): mixed
            {
                return $this->storage[$key] ?? null;
            }
        };
    }

    /**
     * @test
     */
    public function failsWhenProvidedFileCannotBeRead(): void
    {
        $path = 'xxx';
        $this->expectExceptionMessage("Could not open `{$path}`");

        $factory = new GenericTestConfigFactory($this->getStorage());

        $factory->make($path);
    }

    /**
     * @test
     */
    public function failsWhenProvidedFileHasincompleteMetaDataBlock(): void
    {
        $path = 'incomplete-meta-data-block';
        $this->expectExceptionMessage("Could not locate meta data end for file `{$path}`");

        $factory = new GenericTestConfigFactory($this->getStorage(['incomplete-meta-data-block' => '/*---']));

        $factory->make($path);
    }

    /**
     * @test
     * @dataProvider provideEmptyMissingOrMalformedMetaData
     */
    public function returnsPairOfBasicTestConfigsWhenMetaDataBlockIsMissingEmptyOrMalformed(string $content): void
    {
        $expected = [
            new GenericTestConfig('content', "\"use strict\";\n{$content}", [], [TestConfigInclude::assert, TestConfigInclude::sta], [], []),
            new GenericTestConfig('content', $content, [], [TestConfigInclude::assert, TestConfigInclude::sta], [], [])
        ];
        $factory = new GenericTestConfigFactory($this->getStorage(['content' => $content]));

        $actual = $factory->make('content');

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /**
     * @return Generator<string, string[]>
     */
    public static function provideEmptyMissingOrMalformedMetaData(): Generator
    {
        yield 'missing' => [''];
        yield 'empty' => ['/*--- ---*/'];
        yield 'malformed' => ["/*---\na\n   b\n---*/"];
    }

    /**
     * @test
     */
    public function returnsRawTestConfigsWhenRawTagIsPresent(): void
    {
        $content = "/*---\nflags: [raw]\n---*/";
        $expected = [
            new GenericTestConfig('content', $content, [TestConfigFlag::raw], [], [], [])
        ];
        $factory = new GenericTestConfigFactory($this->getStorage(['content' => $content]));

        $actual = $factory->make('content');

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /**
     * @test
     */
    public function returnsModuleTestConfigsWhenModuleTagIsPresent(): void
    {
        $content = "/*---\nflags: [module]\n---*/";
        $expected = [
            new GenericTestConfig('content', $content, [TestConfigFlag::module], [TestConfigInclude::assert, TestConfigInclude::sta], [], [])
        ];
        $factory = new GenericTestConfigFactory($this->getStorage(['content' => $content]));

        $actual = $factory->make('content');

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /**
     * @test
     */
    public function returnsNonStrictTestConfigsWhenNoStrictTagIsPresent(): void
    {
        $content = "/*---\nflags: [noStrict]\n---*/";
        $expected = [
            new GenericTestConfig('content', $content, [TestConfigFlag::noStrict], [TestConfigInclude::assert, TestConfigInclude::sta], [], [])
        ];
        $factory = new GenericTestConfigFactory($this->getStorage(['content' => $content]));

        $actual = $factory->make('content');

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /**
     * @test
     */
    public function returnsStrictTestConfigsWhenOnlyStrictTagIsPresent(): void
    {
        $content = "/*---\nflags: [onlyStrict]\n---*/";
        $expected = [
            new GenericTestConfig('content', "\"use strict\";\n{$content}", [TestConfigFlag::onlyStrict], [TestConfigInclude::assert, TestConfigInclude::sta], [], [])
        ];
        $factory = new GenericTestConfigFactory($this->getStorage(['content' => $content]));

        $actual = $factory->make('content');

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /**
     * @test
     */
    public function includesDonePrintHandleFileWhenAsyncTagIsPresent(): void
    {
        $content = "/*---\nflags: [noStrict, async]\n---*/";
        $expected = [
            new GenericTestConfig('content', $content, [TestConfigFlag::noStrict, TestConfigFlag::async], [TestConfigInclude::assert, TestConfigInclude::sta, TestConfigInclude::doneprintHandle], [], [])
        ];
        $factory = new GenericTestConfigFactory($this->getStorage(['content' => $content]));

        $actual = $factory->make('content');

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /**
     * @test
     */
    public function handlesAllPossibleIncludes(): void
    {
        $includes = implode(', ', array_map(static fn (TestConfigInclude $i): string => basename($i->value), TestConfigInclude::cases()));
        $content = "/*---\nincludes: [{$includes}]\n---*/";
        $expected = [
            new GenericTestConfig('content', "\"use strict\";\n{$content}", [], TestConfigInclude::cases(), [], []),
            new GenericTestConfig('content', $content, [], TestConfigInclude::cases(), [], []),
        ];
        $factory = new GenericTestConfigFactory($this->getStorage(['content' => $content]));

        $actual = $factory->make('content');

        $this->assertEqualsCanonicalizing($expected, $actual);
    }
}
