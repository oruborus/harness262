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

namespace Oru\Harness\Filter;

use Oru\Harness\Contracts\ArgumentsParser;
use Oru\Harness\Contracts\Filter;
use Oru\Harness\Contracts\FilterFactory;
use Oru\Harness\Filter\Exception\MalformedRegularExpressionPatternException;

use function count;

final readonly class GenericFilterFactory implements FilterFactory
{
    public function __construct(
        private ArgumentsParser $argumentsParser
    ) {}

    /**
     * @throws MalformedRegularExpressionPatternException
     */
    public function make(): Filter
    {
        $filters = [];

        if ($this->argumentsParser->hasOption('include')) {
            $filters[] = new FileNameDoesNotMatchRegExpFilter($this->argumentsParser->getOption('include'));
        }

        if ($this->argumentsParser->hasOption('exclude')) {
            $filters[] = new FileNameMatchesRegExpFilter($this->argumentsParser->getOption('exclude'));
        }

        if (count($filters) === 0) {
            return new PassthroughFilter();
        }

        if (count($filters) === 1) {
            return $filters[0];
        }

        return new CompositeFilter($filters);
    }
}
