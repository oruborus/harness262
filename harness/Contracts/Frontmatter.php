<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface Frontmatter
{
    public function description(): string;

    public function esid(): ?string;

    public function info(): ?string;

    public function negative(): ?FrontmatterNegative;

    /**
     * @return FrontmatterInclude[]
     */
    public function includes(): array;

    public function author(): ?string;

    /**
     * @return FrontmatterFlag[]
     */
    public function flags(): array;

    /**
     * @return string[]
     */
    public function features(): array;

    /**
     * @return string[]
     */
    public function locale(): array;

    public function es5id(): ?string;

    public function es6id(): ?string;
}
