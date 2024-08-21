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

namespace Tests\Unit\ArgumentsParser;

use Oru\Harness\ArgumentsParser\CliArgumentsParser;
use Oru\Harness\ArgumentsParser\Exception\InvalidOptionException;
use Oru\Harness\ArgumentsParser\Exception\MissingArgumentException;
use Oru\Harness\ArgumentsParser\Exception\UnknownOptionException;
use Oru\Harness\ArgumentsParser\InputArgumentsParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;

#[CoversClass(InputArgumentsParser::class)]
final class InputArgumentsParserTest extends TestCase
{
    #[Test]
    public function confirmsExistenceOfOption(): void
    {
        $parser = new InputArgumentsParser(
            $this->createConfiguredStub(InputInterface::class, [
                'getOption' => 123,
            ])
        );

        $actual = $parser->hasOption('option');

        $this->assertTrue($actual);
    }
    #[Test]
    public function confirmsNonExistenceOfOption(): void
    {
        $inputStub = $this->createStub(InputInterface::class);
        $inputStub->method('getOption')->willThrowException($this->createStub(InvalidArgumentException::class));
        $parser = new InputArgumentsParser($inputStub);

        $actual = $parser->hasOption('option');

        $this->assertFalse($actual);
    }

    #[Test]
    public function throwsWhenUnknownOptionIsRequested(): void
    {
        $this->expectException(UnknownOptionException::class);

        $inputStub = $this->createStub(InputInterface::class);
        $inputStub->method('getOption')->willThrowException($this->createStub(InvalidArgumentException::class));
        $parser = new InputArgumentsParser($inputStub);

        $parser->getOption('option');
    }

    #[Test]
    public function returnsKnownOptionValue(): void
    {
        $expected = 'VALUE';
        $inputStub = $this->createConfiguredStub(InputInterface::class, [
            'getOption' => $expected,
        ]);
        $parser = new InputArgumentsParser($inputStub);

        $actual = $parser->getOption('option');

        $this->assertSame($expected, $actual);
    }
}
