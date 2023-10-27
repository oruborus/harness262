<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Oru\Harness\Config\OutputConfigFactory;
use Oru\Harness\Contracts\ArgumentsParser;
use Oru\Harness\Contracts\OutputConfig;
use Oru\Harness\Contracts\OutputType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_key_exists;

#[CoversClass(OutputConfigFactory::class)]
final class OutputConfigFactoryTest extends TestCase
{
    /**
     * @param array<string, ?string> $options
     * @param list<string> $rest
     */
    private function createArgumentsParserStub(array $options = [], array $rest = []): ArgumentsParser
    {
        return new class($options, $rest) implements ArgumentsParser
        {
            /**
             * @param array<string, ?string> $options
             * @param list<string> $rest
             */
            public function __construct(
                private array $options,
                private array $rest
            ) {
            }

            public function hasOption(string $option): bool
            {
                return array_key_exists($option, $this->options);
            }

            public function getOption(string $option): string
            {
                return $this->options[$option] ?? '';
            }

            public function rest(): array
            {
                return $this->rest;
            }
        };
    }

    #[Test]
    public function createsConfigForOutput(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub();
        $factory = new OutputConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertInstanceOf(OutputConfig::class, $actual);
    }

    #[Test]
    public function defaultConfigForOutputIsConsole(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub();
        $factory = new OutputConfigFactory($argumentsParserStub);

        $actual = $factory->make([]);

        $this->assertSame([OutputType::Console], $actual->outputTypes());
    }
}
