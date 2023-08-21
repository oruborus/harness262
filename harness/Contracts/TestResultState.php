<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

enum TestResultState
{
    case Success;
    case Pending;
    case Fail;
    case Error;
    case Cache;
    case Skip;
}
