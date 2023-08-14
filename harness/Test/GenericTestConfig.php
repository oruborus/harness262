<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Test;

use Oru\EcmaScript\Harness\Contracts\Frontmatter;
use Oru\EcmaScript\Harness\Contracts\TestConfig;

final readonly class GenericTestConfig implements TestConfig
{
    public function __construct(
        private string $path,
        private string $content,
        private Frontmatter $frontmatter
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

    public function frontmatter(): Frontmatter
    {
        return $this->frontmatter;
    }
}
