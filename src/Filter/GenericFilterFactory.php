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
use Oru\Harness\Contracts\FrontmatterFlag;
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

        if ($this->argumentsParser->hasOption('only-strict')) {
            $filters[] = new FrontmatterFlagFilter(FrontmatterFlag::onlyStrict);
        }

        if ($this->argumentsParser->hasOption('no-strict')) {
            $filters[] = new FrontmatterFlagFilter(FrontmatterFlag::noStrict);
        }

        if ($this->argumentsParser->hasOption('module')) {
            $filters[] = new FrontmatterFlagFilter(FrontmatterFlag::module);
        }

        if ($this->argumentsParser->hasOption('raw')) {
            $filters[] = new FrontmatterFlagFilter(FrontmatterFlag::raw);
        }

        if ($this->argumentsParser->hasOption('async')) {
            $filters[] = new FrontmatterFlagFilter(FrontmatterFlag::async);
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
