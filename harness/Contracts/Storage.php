<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

/**
 * @template TContent
 */
interface Storage
{
    /**
     * @param TContent $content
     */
    public function put(string $key, mixed $content): void;

    /**
     * @return ?TContent
     */
    public function get(string $key): mixed;
}
