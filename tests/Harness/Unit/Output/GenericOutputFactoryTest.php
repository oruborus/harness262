<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Output;

use Generator;
use Oru\EcmaScript\Harness\Contracts\Output;
use Oru\EcmaScript\Harness\Contracts\OutputConfig;
use Oru\EcmaScript\Harness\Contracts\OutputType;
use Oru\EcmaScript\Harness\Output\ConsoleOutput;
use Oru\EcmaScript\Harness\Output\GenericOutputFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericOutputFactory::class)]
final class GenericOutputFactoryTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideOutputConfiguration
     *
     * @param class-string<Output> $outputClassname
     */
    public function returnsTheCorrectOutputClassBasedOnConfiguration(OutputConfig $config, string $outputClassname): void
    {
        $factory = new GenericOutputFactory();

        $actual = $factory->make($config);

        $this->assertInstanceOf(Output::class, $actual);
        $this->assertInstanceOf($outputClassname, $actual);
    }

    /**
     * @return Generator<string, array{0:OutputConfig, 1:class-string<Output>}>
     */
    public static function provideOutputConfiguration(): Generator
    {
        yield 'console output' => [static::createOutputConfig([OutputType::Console]), ConsoleOutput::class];
    }

    /**
     * @param OutputType[] $outputTypes
     */
    private static function createOutputConfig(array $outputTypes): OutputConfig
    {
        return new class($outputTypes) implements OutputConfig
        {
            /**
             * @param OutputType[] $outputTypes
             */
            public function __construct(
                private array $outputTypes
            ) {
            }

            /**
             * @return OutputType[]
             */
            public function outputTypes(): array
            {
                return $this->outputTypes;
            }
        };
    }
}
