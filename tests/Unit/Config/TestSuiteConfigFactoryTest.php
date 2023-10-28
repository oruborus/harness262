<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Oru\Harness\Config\Exception\MalformedRegularExpressionPatternException;
use Oru\Harness\Config\TestSuiteConfigFactory;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Contracts\TestSuiteConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Utility\ArgumentsParser\ArgumentsParserStub;

#[CoversClass(TestSuiteConfigFactory::class)]
final class TestSuiteConfigFactoryTest extends TestCase
{
    #[Test]
    public function createsConfigForTestSuite(): void
    {
        $argumentsParserStub = new ArgumentsParserStub([], [__DIR__ . '/../Fixtures']);
        $factory = new TestSuiteConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertInstanceOf(TestSuiteConfig::class, $actual);
    }

    #[Test]
    public function interpretsAllNonPrefixedArgumentsAsPaths(): void
    {
        $expected = [__DIR__ . '/../Fixtures/PATH0', __DIR__ . '/../Fixtures/PATH1', __DIR__ . '/../Fixtures/PATH2'];
        $argumentsParserStub = new ArgumentsParserStub([], $expected);
        $factory = new TestSuiteConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame($expected, $actual->paths());
    }

    #[Test]
    public function failsWhenPathsIsEmpty(): void
    {
        $this->expectExceptionObject(new RuntimeException('No test path specified. Aborting.'));

        $factory = new TestSuiteConfigFactory(new ArgumentsParserStub());

        $factory->make();
    }

    #[Test]
    public function defaultConfigForCachingIsTrue(): void
    {
        $argumentsParserStub = new ArgumentsParserStub([], [__DIR__ . '/../Fixtures']);
        $factory = new TestSuiteConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertTrue($actual->cache());
    }

    #[Test]
    public function cachingCanBeDisabled(): void
    {
        $argumentsParserStub = new ArgumentsParserStub(['no-cache' => null], [__DIR__ . '/../Fixtures']);
        $factory = new TestSuiteConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertFalse($actual->cache());
    }

    #[Test]
    public function defaultConfigForRunnerModeIsAsync(): void
    {
        $argumentsParserStub = new ArgumentsParserStub([], [__DIR__ . '/../Fixtures']);
        $factory = new TestSuiteConfigFactory($argumentsParserStub);

        $actual = $factory->make([]);

        $this->assertSame(TestRunnerMode::Async, $actual->testRunnerMode());
    }

    #[Test]
    public function configForRunnerModeCanBeSetToLinear(): void
    {
        $argumentsParserStub = new ArgumentsParserStub(['debug' => null], [__DIR__ . '/../Fixtures']);
        $factory = new TestSuiteConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertSame(TestRunnerMode::Linear, $actual->testRunnerMode());
    }

    #[Test]
    public function failsWhenProvidedPathDoesNotExist(): void
    {
        $this->expectExceptionObject(new RuntimeException("Provided path `AAA` does not exist"));

        $factory = new TestSuiteConfigFactory(new ArgumentsParserStub([], ['AAA']));

        $factory->make();
    }

    #[Test]
    public function addsValidDirectoryContentsRecursivelyToPaths(): void
    {
        $argumentsParserStub = new ArgumentsParserStub([], [__DIR__ . '/../Fixtures']);
        $factory = new TestSuiteConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertCount(6, $actual->paths());
    }

    #[Test]
    public function filtersProvidedPathsWithRegularExpressions(): void
    {
        $argumentsParserStub = new ArgumentsParserStub(['filter' => '.*PATH[12].*'], [__DIR__ . '/../Fixtures']);
        $factory = new TestSuiteConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertCount(4, $actual->paths());
    }

    #[Test]
    public function failsWhenProvidedRegularExpressionPatternisMalformed(): void
    {
        $this->expectExceptionObject(new MalformedRegularExpressionPatternException('Compilation failed: missing closing parenthesis at offset 1'));

        $argumentsParserStub = new ArgumentsParserStub(['filter' => '('], [__DIR__ . '/../Fixtures']);
        $factory = new TestSuiteConfigFactory($argumentsParserStub);

        $factory->make();
    }

    #[Test]
    public function errorHandlerFunctionalityIsRestoredAfterRun(): void
    {
        set_error_handler($expected = set_error_handler(null));
        $argumentsParserStub = new ArgumentsParserStub(['filter' => '.*'], [__DIR__ . '/../Fixtures']);
        $factory = new TestSuiteConfigFactory($argumentsParserStub);

        $factory->make();

        set_error_handler($actual = set_error_handler(null));

        $this->assertSame($expected, $actual);
    }
}
