<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface TestConfig extends Config
{
    public function path(): string;

    public function content(): string;

    public function frontmatter(): Frontmatter;
}
