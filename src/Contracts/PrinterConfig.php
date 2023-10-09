<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface PrinterConfig extends Config
{
    public function verbosity(): PrinterVerbosity;
}