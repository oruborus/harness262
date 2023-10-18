<?php

declare(strict_types=1);

namespace Tests\Unit\Assertion;

use Oru\Harness\Assertion\AssertMultiple;
use Oru\Harness\Contracts\Assertion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssertMultiple::class)]
final class AssertMultipleTest extends TestCase
{
    #[Test]
    public function callsAllAssertionsGivenInOrder(): void
    {
        $assertionMocks = [];
        for ($index = 0; $index < 5; $index++) {
            $assertionMock = $this->createMock(Assertion::class);
            $assertionMock->expects($this->once())->method('assert')->willReturnCallback(
                static function () use (&$actual, $index) {
                    $actual .= (string) $index;
                }
            );
            $assertionMocks[] = $assertionMock;
        }

        $assertion = new AssertMultiple(...$assertionMocks);
        $assertion->assert(null);

        $this->assertSame('01234', $actual);
    }

    #[Test]
    public function returnsGivenAssertionsInOrder(): void
    {
        $assertionMocks = [];
        for ($index = 0; $index < 5; $index++) {
            $assertionMocks[] = $this->createMock(Assertion::class);
        }

        $assertion = new AssertMultiple(...$assertionMocks);
        $actual = $assertion->assertions();

        $this->assertSame($assertionMocks, $actual);
    }
}
