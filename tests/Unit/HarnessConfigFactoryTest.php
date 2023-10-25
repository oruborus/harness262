<?php

declare(strict_types=1);

namespace Tests\Unit;

use Generator;
use Oru\Harness\Contracts\ArgumentsParser;
use Oru\Harness\Contracts\OutputConfig;
use Oru\Harness\Contracts\OutputType;
use Oru\Harness\Contracts\PrinterConfig;
use Oru\Harness\Contracts\PrinterVerbosity;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Contracts\TestSuiteConfig;
use Oru\Harness\HarnessConfigFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HarnessConfigFactory::class)]
final class HarnessConfigFactoryTest extends TestCase
{
    #[Test]
    public function createsConfigForOutputPrinterAndTestSuite(): void
    {
        $factory = new HarnessConfigFactory($this->createMock(ArgumentsParser::class));

        $actual = $factory->make([]);

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
    public function defaultConfigForCachingIsTrue(): void
    {
        $factory = new HarnessConfigFactory($this->createMock(ArgumentsParser::class));

        $actual = $factory->make();

        $this->assertTrue($actual->cache());
    }

    #[Test]
    public function cachingCanBeDisabled(): void
    {
        $argumentsParserStub = new class implements ArgumentsParser
        {
            public function hasOption(string $option): bool
            {
                return 'no-cache' === $option;
            }

            public function rest(): array
            {
                return [];
            }
        };
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertFalse($actual->cache());
    }

    #[Test]
    public function defaultConfigForOutputIsConsole(): void
    {
        $factory = new HarnessConfigFactory($this->createMock(ArgumentsParser::class));

        $actual = $factory->make([]);

        $this->assertSame([OutputType::Console], $actual->outputTypes());
    }

    #[Test]
    public function defaultConfigForRunnerModeIsAsync(): void
    {
        $factory = new HarnessConfigFactory($this->createMock(ArgumentsParser::class));

        $actual = $factory->make([]);

        $this->assertSame(TestRunnerMode::Async, $actual->testRunnerMode());
    }

    #[Test]
    public function configForRunnerModeCanBeSetToLinear(): void
    {
        $argumentsParserStub = new class implements ArgumentsParser
        {
            public function hasOption(string $option): bool
            {
                return 'debug' === $option;
            }

            public function rest(): array
            {
                return [];
            }
        };
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame(TestRunnerMode::Linear, $actual->testRunnerMode());
    }

    #[Test]
    public function defaultConfigForVerbosityIsNormal(): void
    {
        $factory = new HarnessConfigFactory($this->createMock(ArgumentsParser::class));

        $actual = $factory->make([]);

        $this->assertSame(PrinterVerbosity::Normal, $actual->verbosity());
    }

    #[Test]
    public function defaultConfigForVerbosityCanBeSetToSilent(): void
    {
        $argumentsParserStub = new class implements ArgumentsParser
        {
            public function hasOption(string $option): bool
            {
                return 'silent' === $option;
            }

            public function rest(): array
            {
                return [];
            }
        };
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame(PrinterVerbosity::Silent, $actual->verbosity());
    }

    #[Test]
    public function defaultConfigForVerbosityCanBeSetToVerbose(): void
    {
        $argumentsParserStub = new class implements ArgumentsParser
        {
            public function hasOption(string $option): bool
            {
                return 'verbose' === $option;
            }

            public function rest(): array
            {
                return [];
            }
        };
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame(PrinterVerbosity::Verbose, $actual->verbosity());
    }

    #[Test]
    public function mixedVerbosityOptionsCancelOutToNormal(): void
    {
        $argumentsParserStub = new class implements ArgumentsParser
        {
            public function hasOption(string $option): bool
            {
                return 'verbose' === $option || 'silent' === $option;
            }

            public function rest(): array
            {
                return [];
            }
        };
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame(PrinterVerbosity::Normal, $actual->verbosity());
    }
}
