<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

enum TestResultState
{
    case Success;
    case Fail;
    case Error;
    case Cache;
    case Skip;
}
