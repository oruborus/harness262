<?php

declare(strict_types=1);

namespace Oru\Harness\Helpers;

use function file_put_contents;
use function unlink;

final class TemporaryFileHandler
{
    public function __construct(
        private string $path,
        string $contents
    ) {
        file_put_contents($this->path, $contents);
    }

    public function __destruct()
    {
        unlink($this->path);
    }

    public function path(): string
    {
        return $this->path;
    }
}
