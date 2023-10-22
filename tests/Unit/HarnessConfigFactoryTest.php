<?php

declare(strict_types=1);

namespace Tests\Unit;

use Generator;
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
        $factory = new HarnessConfigFactory();

        $actual = $factory->make([]);

        $this->assertInstanceOf(OutputConfig::class, $actual);
        $this->assertInstanceOf(PrinterConfig::class, $actual);
        $this->assertInstanceOf(TestSuiteConfig::class, $actual);
    }

    #[Test]
    public function interpretsAllNonPrefixedArgumentsAsPaths(): void
    {
        $factory = new HarnessConfigFactory();

        $actual = $factory->make(['PATH0', '-p', 'PATH1', '--prefixed', 'PATH2']);

        $this->assertSame(['PATH0', 'PATH1', 'PATH2'], $actual->paths());
    }

    #[Test]
    public function pathsDoNotCollideWithLongOptions(): void
    {
        $factory = new HarnessConfigFactory();

        $actual = $factory->make(['verbose']);

        $this->assertSame(['verbose'], $actual->paths());
        $this->assertSame(PrinterVerbosity::Normal, $actual->verbosity());
    }

    #[Test]
    public function defaultConfigForCachingIsTrue(): void
    {
        $factory = new HarnessConfigFactory();

        $actual = $factory->make([]);

        $this->assertTrue($actual->cache());
    }

    #[Test]
    public function cachingCanBeDisabledWithShortOption(): void
    {
        $factory = new HarnessConfigFactory();

        $actual = $factory->make(['-n']);

        $this->assertFalse($actual->cache());
    }

    #[Test]
    public function cachingCanBeDisabledWithLongOption(): void
    {
        $factory = new HarnessConfigFactory();

        $actual = $factory->make(['--no-cache']);

        $this->assertFalse($actual->cache());
    }

    #[Test]
    public function defaultConfigForOutputIsConsole(): void
    {
        $factory = new HarnessConfigFactory();

        $actual = $factory->make([]);

        $this->assertSame([OutputType::Console], $actual->outputTypes());
    }

    #[Test]
    public function defaultConfigForRunnerModeIsAsync(): void
    {
        $factory = new HarnessConfigFactory();

        $actual = $factory->make([]);

        $this->assertSame(TestRunnerMode::Async, $actual->testRunnerMode());
    }

    #[Test]
    public function configForRunnerModeCanBeSetToLinearUsingLongOption(): void
    {
        $factory = new HarnessConfigFactory();

        $actual = $factory->make(['--debug']);

        $this->assertSame(TestRunnerMode::Linear, $actual->testRunnerMode());
    }

    #[Test]
    public function defaultConfigForVerbosityIsNormal(): void
    {
        $factory = new HarnessConfigFactory();

        $actual = $factory->make([]);

        $this->assertSame(PrinterVerbosity::Normal, $actual->verbosity());
    }

    #[Test]
    public function defaultConfigForVerbosityCanBeSetToSilentUsingShortOption(): void
    {
        $factory = new HarnessConfigFactory();

        $actual = $factory->make(['-s']);

        $this->assertSame(PrinterVerbosity::Silent, $actual->verbosity());
    }

    #[Test]
    public function defaultConfigForVerbosityCanBeSetToSilentUsingLongOption(): void
    {
        $factory = new HarnessConfigFactory();

        $actual = $factory->make(['--silent']);

        $this->assertSame(PrinterVerbosity::Silent, $actual->verbosity());
    }

    #[Test]
    public function defaultConfigForVerbosityCanBeSetToVerboseUsingShortOption(): void
    {
        $factory = new HarnessConfigFactory();

        $actual = $factory->make(['-v']);

        $this->assertSame(PrinterVerbosity::Verbose, $actual->verbosity());
    }

    #[Test]
    public function defaultConfigForVerbosityCanBeSetToVerboseUsingLongOption(): void
    {
        $factory = new HarnessConfigFactory();

        $actual = $factory->make(['--verbose']);

        $this->assertSame(PrinterVerbosity::Verbose, $actual->verbosity());
    }

    #[Test]
    #[DataProvider('provideVerbosityOptions')]
    /**
     * @param string[] $options
     */
    public function mixedVerbosityOptionsCancelOutToNormal(array $options): void
    {
        $factory = new HarnessConfigFactory();

        $actual = $factory->make($options);

        $this->assertSame(PrinterVerbosity::Normal, $actual->verbosity());
    }

    /**
     * @return Generator<string, string[]> 
     */
    public static function provideVerbosityOptions(): Generator
    {
        yield 'short silent short verbose' => [['-s', '-v']];
        yield 'short silent long verbose' => [['-s', '--verbose']];
        yield 'long silent short verbose' => [['--silent', '-v']];
        yield 'long silent long verbose' => [['--silent', '--verbose']];
    }
}
