<?php

declare(strict_types=1);

namespace Tests\Unit;

use Oru\Harness\Contracts\ArgumentsParser;
use Oru\Harness\Contracts\OutputConfig;
use Oru\Harness\Contracts\OutputType;
use Oru\Harness\Contracts\PrinterConfig;
use Oru\Harness\Contracts\PrinterVerbosity;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Contracts\TestSuiteConfig;
use Oru\Harness\HarnessConfigFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function array_key_exists;

#[CoversClass(HarnessConfigFactory::class)]
final class HarnessConfigFactoryTest extends TestCase
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
    public function createsConfigForOutputPrinterAndTestSuite(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub([], ['path']);
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertInstanceOf(OutputConfig::class, $actual);
        $this->assertInstanceOf(PrinterConfig::class, $actual);
        $this->assertInstanceOf(TestSuiteConfig::class, $actual);
    }

    #[Test]
    public function interpretsAllNonPrefixedArgumentsAsPaths(): void
    {
        $expected = ['PATH0', 'PATH1', 'PATH2'];
        $factory = new HarnessConfigFactory($this->createConfiguredMock(
            ArgumentsParser::class,
            ['rest' => $expected]
        ));

        $actual = $factory->make();

        $this->assertSame($expected, $actual->paths());
    }

    #[Test]
    public function failsWhenPathsIsEmpty(): void
    {
        $this->expectExceptionObject(new RuntimeException('No test path specified. Aborting.'));

        $factory = new HarnessConfigFactory($this->createMock(ArgumentsParser::class));

        $factory->make();
    }

    #[Test]
    public function defaultConfigForCachingIsTrue(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub([], ['path']);
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertTrue($actual->cache());
    }

    #[Test]
    public function cachingCanBeDisabled(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub(['no-cache' => null], ['path']);
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertFalse($actual->cache());
    }

    #[Test]
    public function defaultConfigForOutputIsConsole(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub([], ['path']);
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make([]);

        $this->assertSame([OutputType::Console], $actual->outputTypes());
    }

    #[Test]
    public function defaultConfigForRunnerModeIsAsync(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub([], ['path']);
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make([]);

        $this->assertSame(TestRunnerMode::Async, $actual->testRunnerMode());
    }

    #[Test]
    public function configForRunnerModeCanBeSetToLinear(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub(['debug' => null], ['path']);
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame(TestRunnerMode::Linear, $actual->testRunnerMode());
    }

    #[Test]
    public function defaultConfigForVerbosityIsNormal(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub([], ['path']);
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make([]);

        $this->assertSame(PrinterVerbosity::Normal, $actual->verbosity());
    }

    #[Test]
    public function defaultConfigForVerbosityCanBeSetToSilent(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub(['silent' => null], ['path']);
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame(PrinterVerbosity::Silent, $actual->verbosity());
    }

    #[Test]
    public function defaultConfigForVerbosityCanBeSetToVerbose(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub(['verbose' => null], ['path']);
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame(PrinterVerbosity::Verbose, $actual->verbosity());
    }

    #[Test]
    public function mixedVerbosityOptionsCancelOutToNormal(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub(['silent' => null, 'verbose' => null], ['path']);
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame(PrinterVerbosity::Normal, $actual->verbosity());
    }
}
