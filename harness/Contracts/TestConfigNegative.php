<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface TestConfigNegative
{
    public function phase(): TestConfigNegativePhase;

    public function type(): string;
}
