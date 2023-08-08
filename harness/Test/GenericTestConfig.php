<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Test;

use Oru\EcmaScript\Harness\Contracts\TestConfig;

final readonly class GenericTestConfig implements TestConfig
{
    /**
     * @param string[] $flags
     * @param string[] $includes
     * @param string[] $features
     * @param array{'phase':'parse'|'resolution'|'runtime','type':string} $negative
     */
    public function __construct(
        private string $path,
        private string $content,
        private array $flags,
        private array $includes,
        private array $features,
        private array $negative
    ) {
    }

    public function path(): string
    {
        return $this->path;
    }

    public function content(): string
    {
        return $this->content;
    }

    /**
     * @return string[]
     */
    public function flags(): array
    {
        return $this->flags;
    }

    /**
     * @return string[]
     */
    public function includes(): array
    {
        return $this->includes;
    }

    /**
     * @return string[]
     */
    public function features(): array
    {
        return $this->features;
    }

    /**
     * @return array{'phase':'parse'|'resolution'|'runtime','type':string}
     */
    public function negative(): array
    {
        return $this->negative;
    }
}
