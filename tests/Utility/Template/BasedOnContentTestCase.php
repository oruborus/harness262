<?php

declare(strict_types=1);

use Oru\Harness\Box\TestConfigFromStdinBox;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\TestRunner\GenericTestResult;

require './vendor/autoload.php';

$config = (new TestConfigFromStdinBox())->unbox();

$resultState = match ($config->content()) {
    'success' => TestResultState::Success,
    'error' => TestResultState::Error,
    'failure' => TestResultState::Fail,
    'skip' => TestResultState::Skip,
};

echo serialize(new GenericTestResult($resultState, 'path', [], 0));
