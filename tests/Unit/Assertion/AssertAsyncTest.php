<?php

declare(strict_types=1);

namespace Tests\Unit\Assertion;

use Oru\Harness\Assertion\AssertAsync;
use Oru\Harness\Assertion\Exception\AssertionFailedException;
use Oru\Harness\Assertion\Exception\EngineException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversClass(AssertAsync::class)]
final class AssertAsyncTest extends TestCase
{
    #[Test]
    public function throwsWhenProvidedValueIsNotAString(): void
    {
        $this->expectExceptionObject(new EngineException('Expected string output'));

        $assertion = new AssertAsync();

        $assertion->assert(false);
    }

    #[Test]
    public function throwsWhenProvidedValueIsNotAStringWithCorrectStartSequence(): void
    {
        $this->expectExceptionObject(new EngineException('Expected string output to start with `Test262:AsyncTestFailure:` in case of failure, got: "WRONG"'));

        $assertion = new AssertAsync();

        $assertion->assert('WRONG');
    }

    #[Test]
    public function throwsWhenProvidedValueAssertionExceptionWhenAsyncTestFailed(): void
    {
        $expectedException = null;
        $assertion = new AssertAsync();

        try {
            $assertion->assert('Test262:AsyncTestFailure:Test262Error:Something went wrong');
        } catch (Throwable $throwable) {
            $expectedException = $throwable;
        }

        $this->assertInstanceOf(AssertionFailedException::class, $expectedException);
        $this->assertSame('Test262Error:Something went wrong', $expectedException->getMessage());
    }

    #[Test]
    public function completesCorrectlyWHenStringMatchesTheCompleteSequence(): void
    {
        $assertion = new AssertAsync();

        $actual = $assertion->assert('Test262:AsyncTestComplete');

        $this->assertNull($actual);
    }
}
