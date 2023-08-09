<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

enum TestConfigNegativePhase
{
    case parse;
    case resolution;
    case runtime;
}
