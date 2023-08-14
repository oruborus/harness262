<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Test;

use Oru\EcmaScript\Harness\Contracts\Storage;
use Oru\EcmaScript\Harness\Frontmatter\GenericFrontmatter;
use Oru\EcmaScript\Harness\Test\Exception\MissingFrontmatterException;
use Oru\EcmaScript\Harness\Test\GenericTestConfig;
use Oru\EcmaScript\Harness\Test\GenericTestConfigFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
    public function failsOnMissingFrontmatter(): void
    {
        $this->expectExceptionObject(new MissingFrontmatterException('Provided test file does not contain a frontmatter section'));

        $factory = new GenericTestConfigFactory($this->getStorage(['content' => '']));
        $factory->make('content');
    }

    /**
     * @test
     */
    public function returnsRawTestConfigsWhenRawTagIsPresent(): void
    {
        $frontmatter = "description: required\nflags: [raw]";
        $content = "/*---\n{$frontmatter}\n---*/";
        $expected = [
            new GenericTestConfig('content', $content, new GenericFrontmatter($frontmatter))
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
        $frontmatter = "description: required\nflags: [module]";
        $content = "/*---\n{$frontmatter}\n---*/";
        $expected = [
            new GenericTestConfig('content', $content, new GenericFrontmatter($frontmatter))
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
        $frontmatter = "description: required\nflags: [noStrict]";
        $content = "/*---\n{$frontmatter}\n---*/";
        $expected = [
            new GenericTestConfig('content', $content, new GenericFrontmatter($frontmatter))
        ];
        $factory = new GenericTestConfigFactory($this->getStorage(['content' => $content]));

        $actual = $factory->make('content');

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /**
     * @test
     */
    public function emptyLinesAreIgnored(): void
    {
        $frontmatter = "description: required\nflags: [noStrict]\n\n\nincludes: [doneprintHandle.js]";
        $content = "/*---\n{$frontmatter}\n---*/";
        $expected = [
            new GenericTestConfig('content', $content, new GenericFrontmatter($frontmatter))
        ];
        $factory = new GenericTestConfigFactory($this->getStorage(['content' => $content]));

        $actual = $factory->make('content');

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /**
     * @test
     */
    public function indentationIsHandled(): void
    {
        $content = "/*---\n   description: required\n   flags: [noStrict]\n   includes: [doneprintHandle.js]\n---*/";
        $expected = [
            new GenericTestConfig('content', $content, new GenericFrontmatter("description: required\nflags: [noStrict]\nincludes: [doneprintHandle.js]"))
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
        $frontmatter = "description: required\nflags: [onlyStrict]";
        $content = "/*---\n{$frontmatter}\n---*/";
        $expected = [
            new GenericTestConfig('content', "\"use strict\";\n{$content}", new GenericFrontmatter($frontmatter))
        ];
        $factory = new GenericTestConfigFactory($this->getStorage(['content' => $content]));

        $actual = $factory->make('content');

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /**
     * @test
     */
    public function returnsStrictAndNonStrictTestConfigsWhenNoRelatedFlagIsPresent(): void
    {
        $frontmatter = "description: required";
        $content = "/*---\n{$frontmatter}\n---*/";
        $expected = [
            new GenericTestConfig('content', "\"use strict\";\n{$content}", new GenericFrontmatter($frontmatter)),
            new GenericTestConfig('content', $content, new GenericFrontmatter($frontmatter))
        ];
        $factory = new GenericTestConfigFactory($this->getStorage(['content' => $content]));

        $actual = $factory->make('content');

        $this->assertEqualsCanonicalizing($expected, $actual);
    }
}
