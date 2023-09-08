<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Cache;

use Generator;
use Oru\EcmaScript\Harness\Cache\GenericCacheRepository;
use Oru\EcmaScript\Harness\Contracts\Storage;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestResult;
use Oru\EcmaScript\Harness\Contracts\TestResultState;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_put_contents;
use function unlink;

#[CoversClass(GenericCacheRepository::class)]
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
    #[DataProvider('provideMalformedCacheData')]
    public function returnsNullWhenCachedDataIsMalformed(mixed $data): void
    {
        $repository = new GenericCacheRepository(
            $this->createConfiguredMock(Storage::class, ['get' => $data]),
            static fn (mixed $_): string => '1'
        );
        $config = $this->createMock(TestConfig::class);

        $actual = $repository->get($config);

        $this->assertNull($actual);
    }

    /**
     * @return Generator<string, mixed[]>
     */
    public static function provideMalformedCacheData(): Generator
    {
        yield 'non-object'                            => [123];
        yield 'object without hash'                   => [(object)['usedFiles' => []]];
        yield 'object without used files'             => [(object)['hash' => 'hash']];
        yield 'object with incorrect hash'            => [(object)['hash' => 'hash', 'usedFiles' => []]];
        yield 'object without result'                 => [(object)['hash' => '1', 'usedFiles' => []]];
        yield 'object with non `TestResult` result'   => [(object)['hash' => '1', 'usedFiles' => [], 'result' => 123]];
        yield 'object with non array used files'      => [(object)['hash' => '1', 'usedFiles' => 'not an array', 'result' => 123]];
        yield 'object with malformed used files'      => [(object)['hash' => '1', 'usedFiles' => ['a' => 1, 'b' => 2], 'result' => 123]];
        yield 'object with malformed used files keys' => [(object)['hash' => '1', 'usedFiles' => ['a', 'b'], 'result' => 123]];
    }
}
