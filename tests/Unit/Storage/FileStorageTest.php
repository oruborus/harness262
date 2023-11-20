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

namespace Tests\Unit\Storage;

use Oru\Harness\Storage\FileStorage;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_exists;
use function is_dir;
use function mkdir;
use function rmdir;
use function unlink;

#[CoversClass(FileStorage::class)]
final class FileStorageTest extends TestCase
{
    #[After]
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

    #[Test]
    public function createsBaseDirectoryWhenItNotExists(): void
    {
        new FileStorage(__DIR__ . '/test');

        $this->assertDirectoryExists(__DIR__ . '/test');
    }

    #[Test]
    public function canStoreStringsInFile(): void
    {
        $storage = new FileStorage(__DIR__);
        $expected = 'TESTTESTTEST';

        $storage->put('test', $expected);
        $actual = $storage->get('test');

        $this->assertFileExists(__DIR__ . '/test');
        $this->assertEquals($expected, $actual);
    }

    #[Test]
    public function returnsNullIfFileDoesNotExist(): void
    {
        $storage = new FileStorage(__DIR__);

        $actual = $storage->get('test');

        $this->assertFileDoesNotExist(__DIR__ . '/test');
        $this->assertNull($actual);
    }

    #[Test]
    public function returnsNullIfFileCannotBeRead(): void
    {
        mkdir(__DIR__ . '/test');
        $storage = new FileStorage(__DIR__);

        $actual = $storage->get('test');

        $this->assertNull($actual);
    }
}
