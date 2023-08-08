<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Storage;

use Oru\EcmaScript\Harness\Storage\FileStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Test262\TestCase;

use function file_exists;
use function is_dir;
use function mkdir;
use function rmdir;
use function unlink;

#[CoversClass(FileStorage::class)]
final class FileStorageTest extends TestCase
{
    /**
     * @after
     */
    public function cleanLocalFileSystem(): void
    {
        if (file_exists(__DIR__ . '/test')) {
            if (is_dir(__DIR__ . '/test')) {
                rmdir(__DIR__ . '/test');
            } else {
                unlink(__DIR__ . '/test');
            }
        }
    }

    /**
     * @test
     */
    public function createsBaseDirectoryWhenItNotExists(): void
    {
        new FileStorage(__DIR__ . '/test');

        $this->assertDirectoryExists(__DIR__ . '/test');
    }

    /**
     * @test
     */
    public function canStoreObjectsInFile(): void
    {
        $storage = new FileStorage(__DIR__);
        $expected = (object)['a' => 123];

        $storage->put('test', $expected);
        $actual = $storage->get('test');

        $this->assertFileExists(__DIR__ . '/test');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function canStoreNameSpacedObjectsInFile(): void
    {
        $storage = new FileStorage(__DIR__);

        $expected = new FileStorageFixture('A');

        $storage->put('test', $expected);
        $actual = $storage->get('test');

        $this->assertFileExists(__DIR__ . '/test');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function returnsNullIfFileDoesNotExist(): void
    {
        $storage = new FileStorage(__DIR__);

        $actual = $storage->get('test');

        $this->assertFileDoesNotExist(__DIR__ . '/test');
        $this->assertNull($actual);
    }

    /**
     * @test
     */
    public function returnsNullIfFileCannotBeRead(): void
    {
        mkdir(__DIR__ . '/test');
        $storage = new FileStorage(__DIR__);

        $actual = $storage->get('test');

        $this->assertNull($actual);
    }
}
