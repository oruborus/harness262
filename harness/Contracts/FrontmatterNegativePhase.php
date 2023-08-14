<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

enum FrontmatterNegativePhase: string
{
    case parse      = 'parse';
    case resolution = 'resolution';
    case runtime    = 'runtime';
}
