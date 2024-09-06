<?php

/**
 * Copyright (c) 2024, Felix Jahn
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

use Oru\Harness\Helpers\Serializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Serializer::class)]
final class SerializerTest extends TestCase
{
    #[Test]
    public function works(): void
    {
        $serializer = new Serializer();

        $expected = $this;
        $intermediate = $serializer->serialize($expected);
        $actual = $serializer->unserialize($intermediate);

        $this->assertIsString($intermediate);
        $this->assertEquals($expected, $actual);
    }
}
