<?php

declare(strict_types=1);

namespace Tests\Utility\StreamWrapper;

use function min;
use function strlen;
use function substr;

final class TestStreamWrapper
{
    public mixed $context;

    private static string $content = '';

    private static int $position = 0;

    public function  stream_close(): void
    {
    }

    public function  stream_eof(): bool
    {
        return static::$position >= strlen(static::$content);
    }

    public function stream_flush(): bool
    {
        return true;
    }

    public function stream_open(
        string $path,
        string $mode,
        int $options,
        ?string &$opened_path
    ): bool {
        return true;
    }

    public function stream_read(int $count): string|false
    {
        $count = min($count, strlen(static::$content) - static::$position);
        $result = substr(static::$content, static::$position, $count);
        static::$position += $count;

        return $result;
    }

    public function stream_stat(): array|false
    {
        return [];
    }

    public function stream_write(string $data): int
    {
        $count = strlen($data);
        static::$content .= $data;

        return $count;
    }
}
