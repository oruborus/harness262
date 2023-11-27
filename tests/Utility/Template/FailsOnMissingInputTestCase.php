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

use Oru\Harness\Box\TestCaseFromStdinBox;

require './vendor/autoload.php';

(new TestCaseFromStdinBox())->unbox();

echo serialize(new RuntimeException('SUCCESS'));
