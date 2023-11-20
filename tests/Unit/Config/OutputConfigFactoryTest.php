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

namespace Tests\Unit\Config;

use Oru\Harness\Config\OutputConfigFactory;
use Oru\Harness\Contracts\OutputConfig;
use Oru\Harness\Contracts\OutputType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Utility\ArgumentsParser\ArgumentsParserStub;

#[CoversClass(OutputConfigFactory::class)]
final class OutputConfigFactoryTest extends TestCase
{
    #[Test]
    public function createsConfigForOutput(): void
    {
        $argumentsParserStub = new ArgumentsParserStub();
        $factory = new OutputConfigFactory($argumentsParserStub);

        $actual = $factory->make();

        $this->assertInstanceOf(OutputConfig::class, $actual);
    }

    #[Test]
    public function defaultConfigForOutputIsConsole(): void
    {
        $argumentsParserStub = new ArgumentsParserStub();
        $factory = new OutputConfigFactory($argumentsParserStub);

        $actual = $factory->make([]);

        $this->assertSame([OutputType::Console], $actual->outputTypes());
    }
}
