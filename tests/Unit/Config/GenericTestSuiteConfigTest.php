<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Oru\Harness\Config\GenericTestSuiteConfig;
use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\TestRunnerMode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericTestSuiteConfig::class)]
final class GenericTestSuiteConfigTest extends TestCase
{
    #[Test]
    public function actsAsValueObject(): void
    {
        $expectedPaths          = ['path/to/file1', 'path/to/file2'];
        $expectedCache          = false;
        $expectedConcurrency    = 123;
        $expectedTestRunnerMode = TestRunnerMode::Linear;
        $expectedStopOnCharacteristic = StopOnCharacteristic::Defect;

        $actual = new GenericTestSuiteConfig(
            $expectedPaths,
            $expectedCache,
            $expectedConcurrency,
            $expectedTestRunnerMode,
            $expectedStopOnCharacteristic
        );

        $this->assertSame($expectedPaths, $actual->paths());
        $this->assertSame($expectedCache, $actual->cache());
        $this->assertSame($expectedConcurrency, $actual->concurrency());
        $this->assertSame($expectedTestRunnerMode, $actual->testRunnerMode());
        $this->assertSame($expectedStopOnCharacteristic, $actual->StopOnCharacteristic());
    }
}
