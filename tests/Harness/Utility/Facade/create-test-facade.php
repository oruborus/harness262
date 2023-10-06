<?php

declare(strict_types=1);

use Oru\EcmaScript\Harness\Contracts\Facade;
use Tests\Harness\Utility\Facade\TestFacade;

require './vendor/autoload.php';

return static fn (): Facade => new TestFacade();
