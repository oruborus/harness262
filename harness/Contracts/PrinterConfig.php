<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

interface PrinterConfig extends Config
{
    public function verbosity(): PrinterVerbosity;
}
