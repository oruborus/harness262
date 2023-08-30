<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

/**
 * @template TReturn
 */
interface Box
{
    /**
     * @return TReturn
     */
    public function unbox(): mixed;
}
