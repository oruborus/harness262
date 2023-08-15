<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Test;

use Oru\EcmaScript\Harness\Contracts\TestResultState;
use Oru\EcmaScript\Harness\Test\GenericTestResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(GenericTestResult::class)]
final class GenericTestResultTest extends TestCase
{
    #[Test]
    public function actsAsValueObject(): void
    {
        $expectedState = TestResultState::Error;
        $expectedUsedFiles = ['A', 'B'];
        $expectedDuration = 123;
        $expectedThrowable = new RuntimeException('Error');

        $actual = new GenericTestResult($expectedState, $expectedUsedFiles, $expectedDuration, $expectedThrowable);

        $this->assertSame($expectedState, $actual->state());
        $this->assertSame($expectedUsedFiles, $actual->usedFiles());
        $this->assertSame($expectedDuration, $actual->duration());
        $this->assertSame($expectedThrowable, $actual->throwable());
    }
}
