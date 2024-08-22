<?php

/**
 * Copyright (c) 2023-2024, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Tests\Unit\Assertion;

use Oru\Harness\Assertion\AssertAsync;
use Oru\Harness\Assertion\Exception\AssertionFailedException;
use Oru\Harness\Assertion\Exception\EngineException;
use Oru\Harness\Contracts\Assertion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Throwable;

use function PHPUnit\Framework\once;

#[CoversClass(AssertAsync::class)]
final class AssertAsyncTest extends TestCase
{
    private function createAssertion(
        ?Assertion $assertion = null,
    ): Assertion {
        return new AssertAsync(
            $assertion ?? $this->createStub(Assertion::class),
        );
    }

    #[Test]
    public function throwsWhenProvidedValueIsNotAString(): void
    {
        $this->expectExceptionObject(new EngineException('Expected string output'));

        $assertionMock = $this->createMock(Assertion::class);
        $assertionMock->expects($this->once())->method('assert');
        $assertion = $this->createAssertion(
            assertion: $assertionMock,
        );

        $assertion->assert(false);
    }

    #[Test]
    public function failsWhenPrintFunctionWasNotCalledWithinTheTestRun(): void
    {
        $this->expectExceptionObject(new AssertionFailedException('The implementation-defined `print` function has not been invoked during test execution'));
        $assertion = $this->createAssertion();

        $assertion->assert('');
    }

    #[Test]
    public function throwsWhenProvidedValueIsNotAStringWithCorrectStartSequence(): void
    {
        $this->expectExceptionObject(new EngineException('Expected string output to start with `Test262:AsyncTestFailure:` in case of failure, got: "WRONG"'));
        $assertion = $this->createAssertion();

        $assertion->assert('WRONG');
    }

    #[Test]
    public function throwsWhenProvidedValueAssertionExceptionWhenAsyncTestFailed(): void
    {
        $expectedException = null;
        $assertion = $this->createAssertion();

        try {
            $assertion->assert('Test262:AsyncTestFailure: Test262Error:Something went wrong');
        } catch (Throwable $throwable) {
            $expectedException = $throwable;
        }

        $this->assertInstanceOf(AssertionFailedException::class, $expectedException);
        $this->assertSame('Test262Error:Something went wrong', $expectedException->getMessage());
    }

    #[Test]
    public function completesCorrectlyWhenStringMatchesTheCompleteSequence(): void
    {
        $assertion = $this->createAssertion();

        $actual = $assertion->assert('Test262:AsyncTestComplete');

        $this->assertNull($actual);
    }
}
