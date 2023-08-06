<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Output;

use Oru\EcmaScript\Harness\Contracts\Output;

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
