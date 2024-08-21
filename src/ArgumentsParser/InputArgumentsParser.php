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

namespace Oru\Harness\ArgumentsParser;

use Oru\Harness\ArgumentsParser\Exception\UnknownOptionException;
use Oru\Harness\Contracts\ArgumentsParser;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;

final readonly class InputArgumentsParser implements ArgumentsParser
{
    public function __construct(
        private InputInterface $input,
    ) {}

    public function hasOption(string $option): bool
    {
        try {
            return (bool) $this->input->getOption($option);
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /** @throws UnknownOptionException */
    public function getOption(string $option): string
    {
        try {
            /** @var string */
            return $this->input->getOption($option);
        } catch (InvalidArgumentException) {
            throw new UnknownOptionException("Unknown option `{$option}` provided");
        }
    }

    /** @return list<string> */
    public function rest(): array
    {
        /** @var list<string> */
        return $this->input->getArguments()['paths'];
    }
}
