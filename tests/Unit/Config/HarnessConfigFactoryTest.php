<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Oru\Harness\Config\HarnessConfigFactory;
use Oru\Harness\Contracts\ArgumentsParser;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Contracts\TestSuiteConfig;
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
    public function createsConfigForTestSuite(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub([], [__DIR__ . '/../Fixtures']);
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertInstanceOf(TestSuiteConfig::class, $actual);
    }

    #[Test]
    public function interpretsAllNonPrefixedArgumentsAsPaths(): void
    {
        $expected = [__DIR__ . '/../Fixtures/PATH0', __DIR__ . '/../Fixtures/PATH1', __DIR__ . '/../Fixtures/PATH2'];
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
        $argumentsParserStub = $this->createArgumentsParserStub([], [__DIR__ . '/../Fixtures']);
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertTrue($actual->cache());
    }

    #[Test]
    public function cachingCanBeDisabled(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub(['no-cache' => null], [__DIR__ . '/../Fixtures']);
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertFalse($actual->cache());
    }

    #[Test]
    public function defaultConfigForRunnerModeIsAsync(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub([], [__DIR__ . '/../Fixtures']);
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make([]);

        $this->assertSame(TestRunnerMode::Async, $actual->testRunnerMode());
    }

    #[Test]
    public function configForRunnerModeCanBeSetToLinear(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub(['debug' => null], [__DIR__ . '/../Fixtures']);
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame(TestRunnerMode::Linear, $actual->testRunnerMode());
    }

    #[Test]
    public function failsWhenProvidedPathDoesNotExist(): void
    {
        $this->expectExceptionObject(new RuntimeException("Provided path `AAA` does not exist"));

        $factory = new HarnessConfigFactory($this->createConfiguredMock(
            ArgumentsParser::class,
            ['rest' => ['AAA']]
        ));

        $factory->make();
    }

    #[Test]
    public function addsValidDirectoryContentsRecursivelyToPaths(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub([], [__DIR__ . '/../Fixtures']);
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertCount(6, $actual->paths());
    }

    #[Test]
    public function filtersProvidedPathsWithRegularExpressions(): void
    {
        $argumentsParserStub = $this->createArgumentsParserStub(['filter' => '.*PATH[12].*'], [__DIR__ . '/../Fixtures']);
        $factory = new HarnessConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertCount(4, $actual->paths());
    }
}
