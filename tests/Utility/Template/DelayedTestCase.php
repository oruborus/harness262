<?php

declare(strict_types=1);

use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Test\GenericTestResult;

require './vendor/autoload.php';

usleep(100);

echo serialize(new GenericTestResult(TestResultState::Success, 'path', [], 0));
