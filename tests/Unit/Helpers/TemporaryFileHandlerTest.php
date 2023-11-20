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

namespace Tests\Unit\Helpers;

use Oru\Harness\Helpers\TemporaryFileHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TemporaryFileHandler::class)]
final class TemporaryFileHandlerTest extends TestCase
{
    #[Test]
    public function createsAndDeletesTestTemplateWithConstruction(): void
    {
        $handler = new TemporaryFileHandler('');
        $path = $handler->path();

        $this->assertFileExists($path);

        unset($handler);

        $this->assertFileDoesNotExist($path);
    }

    #[Test]
    public function createdFileContainsProvidedContent(): void
    {
        $expected = 'Expected content';

        $handler = new TemporaryFileHandler($expected);

        $this->assertStringEqualsFile($handler->path(), $expected);
    }
}
