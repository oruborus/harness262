<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

/**
 * @template TReturn
 */
interface Subprocess
{
    /**
     * @return TReturn
     */
    public function run(): mixed;
}
