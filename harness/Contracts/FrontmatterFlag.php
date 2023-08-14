<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

enum FrontmatterFlag: string
{
    case onlyStrict       = 'onlyStrict';
    case noStrict         = 'noStrict';
    case module           = 'module';
    case raw              = 'raw';
    case async            = 'async';
    case generated        = 'generated';
    case CanBlockIsFalse  = 'CanBlockIsFalse';
    case CanBlockIsTrue   = 'CanBlockIsTrue';
    case nonDeterministic = 'non-deterministic';
}
