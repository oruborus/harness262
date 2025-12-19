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

namespace Tests\Unit\Box;

use Oru\Harness\Box\TestCaseFromStdinBox;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Helpers\ErrorHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Tests\Utility\Helpers\StreamWrapperOverride;
use Tests\Utility\StreamWrapper\FailToOpenStreamWrapper;
use Tests\Utility\StreamWrapper\TestStreamWrapper;

use function fclose;
use function fopen;
use function fwrite;
use function serialize;

#[CoversClass(TestCaseFromStdinBox::class)]
final class TestConfigFromStdinBoxTest extends PHPUnitTestCase
{
    /**
     * This test ensures that a failure to open the provided stream is properly escalated.
     * As the failure itself is fabricated we need to check that it is our failure that
     * triggers the error to proof that our test is working properly.
     */
    #[Test]
    public function throwsWhenStdinCouldNotBeOpened(): void
    {
        $this->expectExceptionMessage('Could not open STDIN');
        $_  = new StreamWrapperOverride('php', FailToOpenStreamWrapper::class);
        $__ = new ErrorHandler(function (int $_, string $message): void {
            $this->assertSame('fopen(php://stdin): Failed to open stream: "Tests\Utility\StreamWrapper\FailToOpenStreamWrapper::stream_open" call failed', $message);
        }, E_WARNING);

        new TestCaseFromStdinBox();
    }

    #[Test]
    public function throwsWhenStdinCouldNotBeRead(): void
    {
        $this->expectExceptionMessage('Could not get contents of STDIN');
        $_ = new StreamWrapperOverride('php', TestStreamWrapper::class);

        new TestCaseFromStdinBox();
    }

    #[Test]
    public function throwsWhenStdinContentsAreNotASerializedTestConfig(): void
    {
        $this->expectExceptionMessage('STDIN did not contain a serialized `TestConfig` object');
        $_ = new StreamWrapperOverride('php', TestStreamWrapper::class);

        $stdin = fopen('php://stdin', 'w');
        fwrite($stdin, serialize((object)['NOT A' => 'TestConfig']));
        fclose($stdin);

        new TestCaseFromStdinBox();
    }

    #[Test]
    public function returnsContainedSerializedTestConfig(): void
    {
        $_ = new StreamWrapperOverride('php', TestStreamWrapper::class);

        $stdin = fopen('php://stdin', 'w');
        fwrite($stdin, serialize($this->createStub(TestCase::class)));
        fclose($stdin);

        $actual = (new TestCaseFromStdinBox())->unbox();

        $this->assertInstanceOf(TestCase::class, $actual);
    }
}
