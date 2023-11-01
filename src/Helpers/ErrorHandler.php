<?php

declare(strict_types=1);

namespace Oru\Harness\Helpers;

use function restore_error_handler;
use function set_error_handler;

final class ErrorHandler
{
    /**
     * @param callable(int, string, string=, int=, array<array-key, mixed>=):bool $handler
     */
    public function __construct(mixed $handler, int $errorLevel)
    {
        set_error_handler($handler, $errorLevel);
    }

    public function __destruct()
    {
        restore_error_handler();
    }
};
