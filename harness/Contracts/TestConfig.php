<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface TestConfig extends Config
{
    public function path(): string;

    public function content(): string;

    /**
     * @return string[]
     */
    public function flags(): array;

    /**
     * @return string[]
     */
    public function includes(): array;

    /**
     * @return string[]
     */
    public function features(): array;

    /**
     * @return array{'phase':'parse'|'resolution'|'runtime','type':string}
     */
    public function negative(): array;
}
