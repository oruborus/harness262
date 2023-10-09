<?php

declare(strict_types=1);

use Oru\Harness\Contracts\Facade;
use Tests\Utility\Facade\TestFacade;

require './vendor/autoload.php';

return static fn (): Facade => new TestFacade();
