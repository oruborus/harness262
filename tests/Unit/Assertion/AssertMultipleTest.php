<?php

/**
 * Copyright (c) 2023, Felix Jahn
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
        $assertionStub = [];
        for ($index = 0; $index < 5; $index++) {
            $assertionStub[] = $this->createStub(Assertion::class);
        }

        $assertion = new AssertMultiple(...$assertionStub);
        $actual = $assertion->assertions();

        $this->assertSame($assertionStub, $actual);
    }
}
