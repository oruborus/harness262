<?php

declare(strict_types=1);

use Oru\EcmaScript\Harness\Contracts\TestResultState;
use Oru\EcmaScript\Harness\Test\GenericTestResult;

require './vendor/autoload.php';

echo serialize(new GenericTestResult(TestResultState::Success, [], 0));
