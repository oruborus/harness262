<?php

/**
 * Copyright (c) 2024, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Tests\Unit\Subprocess;

use Fiber;
use Oru\Harness\Contracts\Command;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestResultFactory;
use Oru\Harness\Subprocess\Exception\InvalidReturnValueException;
use Oru\Harness\Subprocess\PhpSubprocess;
use Oru\Harness\Subprocess\PhpSubprocessFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;


#[CoversClass(PhpSubprocessFactory::class)]
final class PhpSubprocessFactoryTest extends PHPUnitTestCase
{
    #[Test]
    public function createsPhpSubprocess(): void
    {
        $subprocessFactory = new PhpSubprocessFactory(
            $this->createStub(Command::class),
            $this->createStub(TestResultFactory::class),
        );

        $actual = $subprocessFactory->make($this->createStub(TestCase::class));

        $this->assertInstanceOf(PhpSubprocess::class, $actual);
    }

    #[Test]
    public function initializesWrappedSubprocessWithCommand(): void
    {
        $expected = 'CORRECT PATH';
        $this->expectExceptionObject(new InvalidReturnValueException($expected));
        $subprocessFactory = new PhpSubprocessFactory(
            $this->createConfiguredStub(Command::class, [
                '__toString' => "-r echo('{$expected}');",
            ]),
            $this->createStub(TestResultFactory::class),
        );

        $subprocess = $subprocessFactory->make($this->createStub(TestCase::class));
        $fiber = new Fiber(function () use ($subprocess): string {
            return $subprocess->run()->path();
        });
        $fiber->start();
        while ($fiber->isSuspended()) {
            $fiber->resume();
        }
        $actual = $fiber->getReturn();

        $this->assertInstanceOf(PhpSubprocess::class, $subprocess);
        $this->assertSame($expected, $actual);
    }
}
