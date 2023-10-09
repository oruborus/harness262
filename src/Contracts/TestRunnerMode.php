<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

enum TestRunnerMode
{
    case Linear;
    case Parallel;
    case Async;
}
