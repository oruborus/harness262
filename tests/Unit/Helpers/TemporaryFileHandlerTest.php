<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use Oru\Harness\Helpers\TemporaryFileHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TemporaryFileHandler::class)]
final class TemporaryFileHandlerTest extends TestCase
{
    const TEMPORARY_FILE_PATH = __DIR__ . '/test';

    #[Test]
    public function createsAndDeletesTestTemplateWithConstruction(): void
    {
        $temporaryFileHandler = new TemporaryFileHandler(static::TEMPORARY_FILE_PATH, '');

        $this->assertFileExists(static::TEMPORARY_FILE_PATH);

        unset($temporaryFileHandler);

        $this->assertFileDoesNotExist(static::TEMPORARY_FILE_PATH);
    }

    #[Test]
    public function createdFileContainsProvidedContent(): void
    {
        $expected = 'Expected content';

        $_ = new TemporaryFileHandler(static::TEMPORARY_FILE_PATH, $expected);

        $this->assertStringEqualsFile(static::TEMPORARY_FILE_PATH, $expected);
    }
}
