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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Tests\Utility\StreamWrapper\FailToOpenStreamWrapper;
use Tests\Utility\StreamWrapper\TestStreamWrapper;

use function fclose;
use function fopen;
use function fwrite;
use function serialize;
use function stream_wrapper_register;
use function stream_wrapper_restore;
use function stream_wrapper_unregister;

#[CoversClass(TestCaseFromStdinBox::class)]
final class TestConfigFromStdinBoxTest extends PHPUnitTestCase
{
    #[Test]
    public function throwsWhenStdinCouldNotBeOpened(): void
    {
        $this->expectExceptionMessage('Could not open STDIN');

        stream_wrapper_unregister('php');
        stream_wrapper_register('php', FailToOpenStreamWrapper::class);

        @new TestCaseFromStdinBox();

        stream_wrapper_restore('php');
    }

    #[Test]
    public function throwsWhenStdinCouldNotBeRead(): void
    {
        $this->expectExceptionMessage('Could not get contents of STDIN');

        stream_wrapper_unregister('php');
        stream_wrapper_register('php', TestStreamWrapper::class);

        new TestCaseFromStdinBox();

        stream_wrapper_restore('php');
    }

    #[Test]
    public function throwsWhenStdinContentsAreNotASerializedTestConfig(): void
    {
        $this->expectExceptionMessage('STDIN did not contain a serialized `TestConfig` object');

        stream_wrapper_unregister('php');
        stream_wrapper_register('php', TestStreamWrapper::class);
        $stdin = fopen('php://stdin', 'w');
        fwrite($stdin, serialize((object)['NOT A' => 'TestConfig']));
        fclose($stdin);

        new TestCaseFromStdinBox();

        stream_wrapper_restore('php');
    }

    #[Test]
    public function returnsContainedSerializedTestConfig(): void
    {
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', TestStreamWrapper::class);
        $stdin = fopen('php://stdin', 'w');
        fwrite($stdin, serialize($this->createMock(TestCase::class)));
        fclose($stdin);

        $actual = (new TestCaseFromStdinBox())->unbox();

        $this->assertInstanceOf(TestCase::class, $actual);

        stream_wrapper_restore('php');
    }
}
