<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

enum TestResultState
{
    case Success;
    case Fail;
    case Error;
    case Cache;
    case Skip;
}
