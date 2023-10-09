<?php

declare(strict_types=1);

namespace Tests\Unit\Storage;

class FileStorageFixture
{
    public function __construct(
        private string|FileStorageFixture $fixture
    ) {
    }
}