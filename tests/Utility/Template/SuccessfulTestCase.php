<?php

declare(strict_types=1);

use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\TestRunner\GenericTestResult;

require './vendor/autoload.php';

echo serialize(new GenericTestResult(TestResultState::Success, 'path', [], 0));
