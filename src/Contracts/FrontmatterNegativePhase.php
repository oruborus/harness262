<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

enum FrontmatterNegativePhase: string
{
    case parse      = 'parse';
    case resolution = 'resolution';
    case runtime    = 'runtime';
}
