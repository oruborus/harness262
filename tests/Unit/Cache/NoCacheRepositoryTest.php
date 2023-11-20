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

namespace Tests\Unit\Cache;

use Oru\Harness\Cache\NoCacheRepository;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NoCacheRepository::class)]
final class NoCacheRepositoryTest extends TestCase
{
    #[Test]
    public function returnsNullOnGet(): void
    {
        $repository = new NoCacheRepository();
        $config = $this->createMock(TestConfig::class);

        $actual = $repository->get($config);

        $this->assertNull($actual);
    }

    #[Test]
    public function returnsNullOnSet(): void
    {
        $repository = new NoCacheRepository();
        $config = $this->createMock(TestConfig::class);
        $result = $this->createConfiguredMock(TestResult::class, [
            'state' => TestResultState::Success,
            'usedFiles' => [],
            'duration' => 0,
            'throwable' => null
        ]);

        $actual = $repository->set($config, $result);

        $this->assertNull($actual);
    }
}
