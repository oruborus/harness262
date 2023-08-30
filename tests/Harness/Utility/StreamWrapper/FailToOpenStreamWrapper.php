<?php

declare(strict_types=1);

namespace Tests\Harness\Utility\StreamWrapper;

final class FailToOpenStreamWrapper
{
    public function stream_open(
        string $path,
        string $mode,
        int $options,
        ?string &$opened_path
    ): bool {
        return false;
    }
}
