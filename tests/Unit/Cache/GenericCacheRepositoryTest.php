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

namespace Tests\Unit\Cache;

use Oru\Harness\Cache\GenericCacheRepository;
use Oru\Harness\Cache\GenericCacheResultRecord;
use Oru\Harness\Contracts\Storage;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultState;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

use function file_put_contents;
use function hash_file;
use function md5;
use function serialize;
use function unlink;

#[CoversClass(GenericCacheRepository::class)]
#[UsesClass(GenericCacheResultRecord::class)]
final class GenericCacheRepositoryTest extends PHPUnitTestCase
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
        $repository = new GenericCacheRepository(
            $this->createMock(Storage::class),
            static fn(TestCase $i): string => md5(serialize($i)),
            static fn(string $i): string => hash_file('haval160,4', $i)
        );
        $testCaseMock = $this->createMock(TestCase::class);

        $actual = $repository->get($testCaseMock);

        $this->assertNull($actual);
    }

    private function getStorage(array $predefined = []): Storage
    {
        return new class ($predefined) implements Storage {
            public function __construct(
                private array $storage
            ) {}

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
        $repository = new GenericCacheRepository(
            $this->getStorage(),
            static fn(TestCase $i): string => md5(serialize($i)),
            static fn(string $i): string => hash_file('haval160,4', $i)
        );
        $testCaseMock = $this->createMock(TestCase::class);
        $result = $this->createConfiguredMock(TestResult::class, [
            'state' => TestResultState::Success,
            'usedFiles' => [__DIR__ . '/A', __DIR__ . '/B'],
            'duration' => 0,
            'throwable' => null
        ]);

        $repository->set($testCaseMock, $result);
        $actual = $repository->get($testCaseMock);

        $this->assertEquals($result, $actual);
    }

    #[Test]
    public function returnsNullWhenCacheFileExistsButUsedFileWasChanged(): void
    {
        $repository = new GenericCacheRepository(
            $this->getStorage(),
            static fn(TestCase $i): string => md5(serialize($i)),
            static fn(string $i): string => hash_file('haval160,4', $i)
        );
        $testCaseMock = $this->createMock(TestCase::class);
        $result = $this->createConfiguredMock(TestResult::class, [
            'state' => TestResultState::Success,
            'usedFiles' => [__DIR__ . '/A', __DIR__ . '/B'],
            'duration' => 0,
            'throwable' => null
        ]);

        $repository->set($testCaseMock, $result);
        file_put_contents(__DIR__ . '/A', 'Changed contents of A');
        $actual = $repository->get($testCaseMock);

        $this->assertNull($actual);
    }

    #[Test]
    public function standardKeyHashDefinitionMatches(): void
    {
        $testCaseMock = $this->createMock(TestCase::class);
        $expected = md5(serialize($testCaseMock));
        $storageMock = $this->createMock(Storage::class);
        $storageMock->expects($this->once())->method('get')->with($expected);
        $repository = new GenericCacheRepository(
            $storageMock,
            static fn(TestCase $i): string => md5(serialize($i)),
            static fn(string $i): string => hash_file('haval160,4', $i)
        );

        $repository->get($testCaseMock);
    }
}
