<?php

/**
 * Copyright (c) 2023, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

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