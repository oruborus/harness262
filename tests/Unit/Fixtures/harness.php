<?php

/**
 * Copyright (c) 2023-2024, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Tests\Unit\Fixtures;

use Oru\EcmaScript\Core\Contracts\Engine;
use PHPUnit\Framework\MockObject\Generator\Generator;

static $engineStub;
$engineStub = (new Generator)->testDouble(
    Engine::class,
    true,
    callOriginalConstructor: false,
    callOriginalClone: false,
    cloneArguments: false,
    allowMockingUnknownTypes: false,
);

return clone $engineStub;
