<?php

declare(strict_types=1);

namespace Tests\Unit\Cache;

use Oru\Harness\Cache\GenericCacheRepository;
use Oru\Harness\Cache\GenericCacheResultRecord;
use Oru\Harness\Contracts\Storage;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultState;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function file_put_contents;
use function md5;
use function serialize;
use function unlink;

#[CoversClass(GenericCacheRepository::class)]
#[UsesClass(GenericCacheResultRecord::class)]
final class GenericCacheRepositoryTest extends TestCase
{
    #[Before]
    public function initializeLocalFileSystem(): void
    {
        file_put_contents(__DIR__ . '/A', 'Contents of A');
        file_put_contents(__DIR__ . '/B', 'Contents of B');
    }

    #[After]
    public function cleanLocalFileSystem(): void
    {
        unlink(__DIR__ . '/A');
        unlink(__DIR__ . '/B');
    }

    #[Test]
    public function returnsNullWhenNoCacheForConfigExists(): void
    {
        $repository = new GenericCacheRepository($this->createMock(Storage::class));
        $config = $this->createMock(TestConfig::class);

        $actual = $repository->get($config);

        $this->assertNull($actual);
    }

    private function getStorage(array $predefined = []): Storage
    {
        return new class($predefined) implements Storage
        {
            public function __construct(
                private array $storage
            ) {
            }

            public function put(string $key, mixed $content): void
            {
                $this->storage[$key] = $content;
            }

            public function get(string $key): mixed
            {
                return $this->storage[$key] ?? null;
            }
        };
    }

    #[Test]
    public function returnsEqualResultWhenCacheFileExists(): void
    {
        $repository = new GenericCacheRepository($this->getStorage());
        $config = $this->createMock(TestConfig::class);
        $result = $this->createConfiguredMock(TestResult::class, [
            'state' => TestResultState::Success,
            'usedFiles' => [__DIR__ . '/A', __DIR__ . '/B'],
            'duration' => 0,
            'throwable' => null
        ]);

        $repository->set($config, $result);
        $actual = $repository->get($config);

        $this->assertEquals($result, $actual);
    }

    #[Test]
    public function returnsNullWhenCacheFileExistsButUsedFileWasChanged(): void
    {
        $repository = new GenericCacheRepository($this->getStorage());
        $config = $this->createMock(TestConfig::class);
        $result = $this->createConfiguredMock(TestResult::class, [
            'state' => TestResultState::Success,
            'usedFiles' => [__DIR__ . '/A', __DIR__ . '/B'],
            'duration' => 0,
            'throwable' => null
        ]);

        $repository->set($config, $result);
        file_put_contents(__DIR__ . '/A', 'Changed contents of A');
        $actual = $repository->get($config);

        $this->assertNull($actual);
    }

    #[Test]
    public function standardKeyHashDefinitionMatches(): void
    {
        $testConfigMock = $this->createMock(TestConfig::class);
        $expected = md5(serialize($testConfigMock));
        $storageMock = $this->createMock(Storage::class);
        $storageMock->expects($this->once())->method('get')->with($expected);
        $repository = new GenericCacheRepository($storageMock);

        $repository->get($testConfigMock);
    }
}
