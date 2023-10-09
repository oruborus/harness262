<?php

declare(strict_types=1);

namespace Oru\Harness\Output;

use Oru\Harness\Contracts\Output;

use const PHP_EOL;

final readonly class ConsoleOutput implements Output
{
    public function write(string $content): void
    {
        echo $content;
    }

    public function writeLn(string $content): void
    {
        $this->write($content . PHP_EOL);
    }
}
