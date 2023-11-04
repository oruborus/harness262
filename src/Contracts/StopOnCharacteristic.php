<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

enum StopOnCharacteristic
{
    case Defect;
    case Error;
    case Failure;
    case Nothing;
}
