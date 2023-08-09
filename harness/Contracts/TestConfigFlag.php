<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

use RuntimeException;

enum TestConfigFlag
{
    case onlyStrict;
    case noStrict;
    case module;
    case raw;
    case async;
    case generated;
    case CanBlockIsFalse;
    case CanBlockIsTrue;
    case nonDeterministic;

    public static function fromString(string $flag): static
    {
        return match ($flag) {
            'onlyStrict'        => static::onlyStrict,
            'noStrict'          => static::noStrict,
            'module'            => static::module,
            'raw'               => static::raw,
            'async'             => static::async,
            'generated'         => static::generated,
            'CanBlockIsFalse'   => static::CanBlockIsFalse,
            'CanBlockIsTrue'    => static::CanBlockIsTrue,
            'non-deterministic' => static::nonDeterministic,
            default => throw new RuntimeException("Unknown flag `{$flag}`")
        };
    }
}
