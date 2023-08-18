<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness;

use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\EcmaScript\EngineImplementation;
use Tests\Test262\Utilities\PrintIntrinsic;
use Tests\Test262\Utilities\S262Intrinsic;

function getEngine(): Engine
{
    return new EngineImplementation(
        hostDefinedProperties: [
            '$262' => S262Intrinsic::class,
            'print' => PrintIntrinsic::class
        ]
    );
}
