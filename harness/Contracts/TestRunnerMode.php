<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

enum TestRunnerMode
{
    case Linear;
    case Parallel;
    case Async;
}
