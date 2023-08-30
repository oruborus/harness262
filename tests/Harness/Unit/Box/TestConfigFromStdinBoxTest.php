<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Box;

use Oru\EcmaScript\Harness\Box\TestConfigFromStdinBox;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Harness\Utility\StreamWrapper\FailToOpenStreamWrapper;
use Tests\Harness\Utility\StreamWrapper\TestStreamWrapper;

use function fclose;
use function fopen;
use function fwrite;
use function serialize;
use function stream_wrapper_register;
use function stream_wrapper_restore;
use function stream_wrapper_unregister;

#[CoversClass(TestConfigFromStdinBox::class)]
final class TestConfigFromStdinBoxTest extends TestCase
{
    #[Test]
    public function throwsWhenStdinCouldNotBeOpened(): void
    {
        $this->expectExceptionMessage('Could not open STDIN');

        stream_wrapper_unregister('php');
        stream_wrapper_register('php', FailToOpenStreamWrapper::class);

        @new TestConfigFromStdinBox();

        stream_wrapper_restore('php');
    }

    #[Test]
    public function throwsWhenStdinCouldNotBeRead(): void
    {
        $this->expectExceptionMessage('Could not get contents of STDIN');

        stream_wrapper_unregister('php');
        stream_wrapper_register('php', TestStreamWrapper::class);

        new TestConfigFromStdinBox();

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

        new TestConfigFromStdinBox();

        stream_wrapper_restore('php');
    }

    #[Test]
    public function returnsContainedSerializedTestConfig(): void
    {
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', TestStreamWrapper::class);
        $stdin = fopen('php://stdin', 'w');
        fwrite($stdin, serialize($this->createMock(TestConfig::class)));
        fclose($stdin);

        $actual = (new TestConfigFromStdinBox())->unbox();

        $this->assertInstanceOf(TestConfig::class, $actual);

        stream_wrapper_restore('php');
    }
}
