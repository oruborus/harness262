<?php

declare(strict_types=1);

namespace Tests\Unit\Output;

use Generator;
use Oru\Harness\Contracts\Output;
use Oru\Harness\Contracts\OutputConfig;
use Oru\Harness\Contracts\OutputType;
use Oru\Harness\Output\ConsoleOutput;
use Oru\Harness\Output\GenericOutputFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericOutputFactory::class)]
final class GenericOutputFactoryTest extends TestCase
{
    /**
     * @param class-string<Output> $outputClassname
     */
    #[Test]
    #[DataProvider('provideOutputConfiguration')]
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
