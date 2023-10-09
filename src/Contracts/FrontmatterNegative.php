<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface FrontmatterNegative
{
    public function phase(): FrontmatterNegativePhase;

    public function type(): string;
}
