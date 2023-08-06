<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Test;

use Oru\EcmaScript\Harness\Contracts\TestConfig;

final readonly class GenericTestConfig implements TestConfig
{
    public function __construct(
        private string $path,

        private string $content,

        /**
         * @var string[] $flags
         */
        private array $flags,

        /**
         * @var string[] $includes
         */
        private array $includes,

        /**
         * @var string[] $features
         */
        private array $features,

        /**
         * @var array{'phase':'parse'|'resolution'|'runtime','type':string} $negative
         */
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
