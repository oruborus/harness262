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

namespace Tests\Unit\Helpers;

use Oru\Harness\Helpers\ErrorHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Throwable;

use function trigger_error;

use const E_USER_WARNING;

#[CoversClass(ErrorHandler::class)]
final class ErrorHandlerTest extends TestCase
{
    #[Test]
    public function callsProvidedMethodWhenErrorOfAppropriateLevelWasTriggered(): void
    {
        $expectedException = $this->createMock(Throwable::class);
        $this->expectExceptionObject($expectedException);
        $level = E_USER_WARNING;

        $_ = new ErrorHandler(fn() => throw $expectedException, $level);

        trigger_error('not used', $level);
    }
}
