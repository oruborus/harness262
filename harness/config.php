<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness;

use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\EcmaScript\Core\Contracts\Values\AbruptCompletion;
use Oru\EcmaScript\EngineImplementation;
use RuntimeException;
use Tests\Test262\Utilities\PrintIntrinsic;
use Tests\Test262\Utilities\S262Intrinsic;
use Throwable;

/**
 * @throws RuntimeException
 */
function getEngine(): Engine
{
    try {
        return new EngineImplementation(
            hostDefinedProperties: [
                '$262' => S262Intrinsic::class,
                'print' => PrintIntrinsic::class
            ]
        );
    } catch (Throwable | AbruptCompletion $throwable) {
        throw new RuntimeException('Could not initialize engine', previous: $throwable);
    }
}
