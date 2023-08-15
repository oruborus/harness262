<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Frontmatter;

use Generator;
use Oru\EcmaScript\Harness\Contracts\Frontmatter;
use Oru\EcmaScript\Harness\Contracts\FrontmatterFlag;
use Oru\EcmaScript\Harness\Contracts\FrontmatterInclude;
use Oru\EcmaScript\Harness\Contracts\FrontmatterNegative;
use Oru\EcmaScript\Harness\Contracts\FrontmatterNegativePhase;
use Oru\EcmaScript\Harness\Frontmatter\Exception\MissingRequiredKeyException;
use Oru\EcmaScript\Harness\Frontmatter\Exception\UnrecognizedFlagException;
use Oru\EcmaScript\Harness\Frontmatter\Exception\UnrecognizedIncludeException;
use Oru\EcmaScript\Harness\Frontmatter\Exception\UnrecognizedKeyException;
use Oru\EcmaScript\Harness\Frontmatter\GenericFrontmatter;
use Oru\EcmaScript\Harness\Frontmatter\GenericFrontmatterNegative;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericFrontmatter::class)]
#[UsesClass(GenericFrontmatterNegative::class)]
final class GenericFrontmatterTest extends TestCase
{
    #[Test]
    public function failsIfDescriptionFieldIsMissing(): void
    {
        $this->expectExceptionObject(new MissingRequiredKeyException("Required frontmatter fields where not provided: description"));

        new GenericFrontmatter('author: anonymous');
    }

    #[Test]
    public function failsIfUnrecognizedFieldIsSupplied(): void
    {
        $field = 'unrecognized';
        $this->expectExceptionObject(new UnrecognizedKeyException("Unrecognized frontmatter fields where provided: {$field}"));

        new GenericFrontmatter("description: test\n{$field}: data");
    }

    #[Test]
    public function canBeConstructed(): void
    {
        $frontmatter = new GenericFrontmatter(
            <<<'EOF'
            description: A complete test frontmatter
            esid: pending
            es5id: pending
            es6id: pending
            info: |
              This is a multiline information of the described test case.
            
              Using multiple lines.
            negative:
              phase: parse
              type: SyntaxError
            includes: []
            author: anonymous
            flags: []
            features: []
            locale: []
            EOF
        );

        $this->assertInstanceOf(Frontmatter::class, $frontmatter);
    }

    #[Test]
    public function returnsDescription(): void
    {
        $expected = 'random text containing unicode but no line terminators 😊';
        $frontmatter = new GenericFrontmatter("description: {$expected}");

        $actual = $frontmatter->description();

        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function returnsEsIdStringWhenProvided(): void
    {
        $expected = 'sec-some-spec';
        $frontmatter = new GenericFrontmatter("description: required\nesid: {$expected}");

        $actual = $frontmatter->esid();

        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function returnsNullWhenEsIdIsNotProvided(): void
    {
        $frontmatter = new GenericFrontmatter("description: required");

        $actual = $frontmatter->esid();

        $this->assertNull($actual);
    }

    #[Test]
    public function returnsNullWhenEsIdIsEmpty(): void
    {
        $frontmatter = new GenericFrontmatter("description: required\nesid: ");

        $actual = $frontmatter->esid();

        $this->assertNull($actual);
    }

    #[Test]
    public function returnsInfoStringWhenProvided(): void
    {
        $expected1 = 'Some multi-line';
        $expected2 = 'information';
        $expected = "{$expected1}\n{$expected2}";
        $frontmatter = new GenericFrontmatter("description: required\ninfo: |\n    {$expected1}\n    {$expected2}");

        $actual = $frontmatter->info();

        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function returnsNullWhenInfoIsNotProvided(): void
    {
        $frontmatter = new GenericFrontmatter("description: required");

        $actual = $frontmatter->info();

        $this->assertNull($actual);
    }

    #[Test]
    public function returnsNullWhenInfoIsEmpty(): void
    {
        $frontmatter = new GenericFrontmatter("description: required\ninfo: ");

        $actual = $frontmatter->info();

        $this->assertNull($actual);
    }

    #[Test]
    public function returnsFrontmatterNegativeWhenProvided(): void
    {
        $expected = new class implements FrontmatterNegative
        {
            public function phase(): FrontmatterNegativePhase
            {
                return FrontmatterNegativePhase::resolution;
            }

            public function type(): string
            {
                return 'ReferenceError';
            }
        };

        $frontmatter = new GenericFrontmatter("description: required\nnegative:\n    phase: resolution\n    type: ReferenceError");

        $actual = $frontmatter->negative();

        $this->assertSame($expected->phase(), $actual->phase());
        $this->assertSame($expected->type(), $actual->type());
    }

    #[Test]
    public function returnsNullWhenNegativeIsNotProvided(): void
    {
        $frontmatter = new GenericFrontmatter("description: required");

        $actual = $frontmatter->negative();

        $this->assertNull($actual);
    }

    #[Test]
    public function returnsNullWhenNegativeIsEmpty(): void
    {
        $frontmatter = new GenericFrontmatter("description: required\nnegative: ");

        $actual = $frontmatter->negative();

        $this->assertNull($actual);
    }

    #[Test]
    public function returnsIncludesListWhenProvided(): void
    {
        $expected = [FrontmatterInclude::assert, FrontmatterInclude::sta];
        $frontmatter = new GenericFrontmatter("description: required\nincludes: [assert.js, sta.js]");

        $actual = $frontmatter->includes();

        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function failsWhenUnrecognizedIncludeIsProvided(): void
    {
        $this->expectExceptionObject(new UnrecognizedIncludeException('Unrecognized frontmatter include was provided: `unrecognized.js`'));

        new GenericFrontmatter("description: required\nincludes: [unrecognized.js]");
    }

    #[Test]
    public function returnsEmptyListWhenIncludesAreNotProvided(): void
    {
        $frontmatter = new GenericFrontmatter("description: required\nflags: [raw]");

        $actual = $frontmatter->includes();

        $this->assertEmpty($actual);
    }

    #[Test]
    public function returnsEmptyListWhenIncludesAreEmpty(): void
    {
        $frontmatter = new GenericFrontmatter("description: required\nflags: [raw]\nincludes: ");

        $actual = $frontmatter->includes();

        $this->assertEmpty($actual);
    }

    #[Test]
    #[DataProvider('provideFlag')]
    public function whenRawFlagIsNotSetIncludesContainAssertAndSta(string $flag): void
    {
        $frontmatter = new GenericFrontmatter("description: required\nflags: [{$flag}]");

        $actual = $frontmatter->includes();

        $this->assertSame(FrontmatterInclude::assert, $actual[0]);
        $this->assertSame(FrontmatterInclude::sta, $actual[1]);
    }

    /**
     * @return Generator<string, array{0: string}>
     */
    public static function provideFlag(): Generator
    {
        foreach (FrontmatterFlag::cases() as $flag) {
            if ($flag === FrontmatterFlag::raw) {
                continue;
            }

            yield $flag->value => [$flag->value];
        }
    }

    #[Test]
    public function whenRawFlagIsSetIncludesAreEmpty(): void
    {
        $frontmatter = new GenericFrontmatter("description: required\nflags: [raw]");

        $actual = $frontmatter->includes();

        $this->assertEmpty($actual);
    }

    #[Test]
    public function handlesAllPossibleIncludes(): void
    {
        $includes = implode(', ', array_map(static fn (FrontmatterInclude $i): string => basename($i->value), FrontmatterInclude::cases()));

        $expected = count(FrontmatterInclude::cases());

        $actual = new GenericFrontmatter("description: required\nincludes: [{$includes}]");

        $this->assertCount($expected, $actual->includes());
    }

    #[Test]
    public function includesDonePrintHandleFileWhenAsyncTagIsPresent(): void
    {
        $actual = new GenericFrontmatter("description: required\nflags: [async]");

        $this->assertContains(FrontmatterInclude::doneprintHandle, $actual->includes());
    }

    #[Test]
    public function returnsAuthorStringWhenProvided(): void
    {
        $expected = 'anonymous';
        $frontmatter = new GenericFrontmatter("description: required\nauthor: {$expected}");

        $actual = $frontmatter->author();

        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function returnsNullWhenAuthorIsNotProvided(): void
    {
        $frontmatter = new GenericFrontmatter("description: required");

        $actual = $frontmatter->author();

        $this->assertNull($actual);
    }

    #[Test]
    public function returnsNullWhenAuthorIsEmpty(): void
    {
        $frontmatter = new GenericFrontmatter("description: required\nauthor: ");

        $actual = $frontmatter->author();

        $this->assertNull($actual);
    }

    #[Test]
    public function returnsFlagsListWhenProvided(): void
    {
        $expected = [FrontmatterFlag::noStrict, FrontmatterFlag::async];
        $frontmatter = new GenericFrontmatter("description: required\nflags: [noStrict, async]");

        $actual = $frontmatter->flags();

        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function failsWhenUnrecognizedFlagIsProvided(): void
    {
        $this->expectExceptionObject(new UnrecognizedFlagException('Unrecognized frontmatter flag was provided: `unrecognized`'));

        new GenericFrontmatter("description: required\nflags: [unrecognized]");
    }

    #[Test]
    public function returnsEmptyListWhenFlagsAreNotProvided(): void
    {
        $frontmatter = new GenericFrontmatter("description: required");

        $actual = $frontmatter->flags();

        $this->assertEmpty($actual);
    }

    #[Test]
    public function returnsEmptyListWhenFlagsAreEmpty(): void
    {
        $frontmatter = new GenericFrontmatter("description: required\nflags: ");

        $actual = $frontmatter->flags();

        $this->assertEmpty($actual);
    }

    #[Test]
    public function returnsFeaturesListWhenProvided(): void
    {
        $expected = ['featureA', 'featureB'];
        $frontmatter = new GenericFrontmatter("description: required\nfeatures: [featureA, featureB]");

        $actual = $frontmatter->features();

        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function returnsEmptyListWhenFeaturesAreNotProvided(): void
    {
        $frontmatter = new GenericFrontmatter("description: required");

        $actual = $frontmatter->features();

        $this->assertEmpty($actual);
    }

    #[Test]
    public function returnsEmptyListWhenFeaturesAreEmpty(): void
    {
        $frontmatter = new GenericFrontmatter("description: required\nfeatures: ");

        $actual = $frontmatter->features();

        $this->assertEmpty($actual);
    }

    #[Test]
    public function returnsLocaleListWhenProvided(): void
    {
        $expected = ['de-DE', 'en-US'];
        $frontmatter = new GenericFrontmatter("description: required\nlocale: [de-DE, en-US]");

        $actual = $frontmatter->locale();

        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function returnsEmptyListWhenLocalesAreNotProvided(): void
    {
        $frontmatter = new GenericFrontmatter("description: required");

        $actual = $frontmatter->locale();

        $this->assertEmpty($actual);
    }

    #[Test]
    public function returnsEmptyListWhenLocalesAreEmpty(): void
    {
        $frontmatter = new GenericFrontmatter("description: required\nlocale: ");

        $actual = $frontmatter->locale();

        $this->assertEmpty($actual);
    }
    #[Test]
    public function returnsEs5IdStringWhenProvided(): void
    {
        $expected = 'sec-some-spec';
        $frontmatter = new GenericFrontmatter("description: required\nes5id: {$expected}");

        $actual = $frontmatter->es5id();

        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function returnsNullWhenEs5IdIsNotProvided(): void
    {
        $frontmatter = new GenericFrontmatter("description: required");

        $actual = $frontmatter->es5id();

        $this->assertNull($actual);
    }

    #[Test]
    public function returnsNullWhenEs5IdIsEmpty(): void
    {
        $frontmatter = new GenericFrontmatter("description: required\nes5id: ");

        $actual = $frontmatter->es5id();

        $this->assertNull($actual);
    }
    #[Test]
    public function returnsEs6IdStringWhenProvided(): void
    {
        $expected = 'sec-some-spec';
        $frontmatter = new GenericFrontmatter("description: required\nes6id: {$expected}");

        $actual = $frontmatter->es6id();

        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function returnsNullWhenEs6IdIsNotProvided(): void
    {
        $frontmatter = new GenericFrontmatter("description: required");

        $actual = $frontmatter->es6id();

        $this->assertNull($actual);
    }

    #[Test]
    public function returnsNullWhenEs6IdIsEmpty(): void
    {
        $frontmatter = new GenericFrontmatter("description: required\nes6id: ");

        $actual = $frontmatter->es6id();

        $this->assertNull($actual);
    }
}
