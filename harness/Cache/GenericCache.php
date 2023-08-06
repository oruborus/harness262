<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Cache;

use Oru\EcmaScript\Harness\Contracts\Cache;

final readonly class GenericCache implements Cache
{
    public function __construct(
        /**
         * @var string[] $usedFiles
         */
        private array $usedFiles,

        private int $duration
    ) {
    }

    public function valid(): bool
    {
        return new \RuntimeException('NOT IMPLEMENTED');
    }

    /**
     * @return string[] 
     */
    public function usedFiles(): array
    {
        return $this->usedFiles;
    }

    public function duration(): int
    {
        return $this->duration;
    }
}
