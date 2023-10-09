<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

enum PrinterVerbosity
{
    case Silent;
    case Normal;
    case Verbose;
}
