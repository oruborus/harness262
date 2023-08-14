<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Frontmatter;

use Generator;
use Oru\EcmaScript\Harness\Contracts\FrontmatterNegativePhase;
use Oru\EcmaScript\Harness\Frontmatter\Exception\UnrecognizedNegativePhaseException;
use Oru\EcmaScript\Harness\Frontmatter\GenericFrontmatterNegative;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericFrontmatterNegative::class)]
final class GenericFrontmatterNegativeTest extends TestCase
{
    /**
     * @test
     * @dataProvider providePhase
     */
    public function canBeCreatedWithDifferentPhases(FrontmatterNegativePhase $expected): void
    {
        $actual = new GenericFrontmatterNegative(['phase' => $expected->value, 'type' => 'x']);

        $this->assertSame($expected, $actual->phase());
        $this->assertSame('x', $actual->type());
    }

    /**
     * @return Generator<string, array{0: string}>
     */
    public static function providePhase(): Generator
    {
        foreach (FrontmatterNegativePhase::cases() as $case) {
            yield $case->value => [$case];
        }
    }

    /**
     * @test
     */
    public function failsWhenUnrecognizedPhaseIsProvided(): void
    {
        $this->expectExceptionObject(new UnrecognizedNegativePhaseException('Unrecognized negative phase `unrecognized`'));

        new GenericFrontmatterNegative(['phase' => 'unrecognized', 'type' => 'does not matter']);
    }
}
