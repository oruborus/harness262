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

namespace Oru\Harness\Config;

use Oru\Harness\Config\Exception\MissingFrontmatterException;
use Oru\Harness\Contracts\Storage;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestCaseFactory;
use Oru\Harness\Contracts\FrontmatterFlag;
use Oru\Harness\Contracts\ImplicitStrictness;
use Oru\Harness\Contracts\TestSuite;
use Oru\Harness\Frontmatter\Exception\MissingRequiredKeyException;
use Oru\Harness\Frontmatter\Exception\ParseException;
use Oru\Harness\Frontmatter\Exception\UnrecognizedKeyException;
use Oru\Harness\Frontmatter\Exception\UnrecognizedNegativePhaseException;
use Oru\Harness\Frontmatter\GenericFrontmatter;
use RuntimeException;

use function array_map;
use function implode;
use function in_array;
use function ltrim;
use function preg_split;
use function reset;
use function strlen;
use function substr;

use const PHP_EOL;
use const PREG_SPLIT_NO_EMPTY;

final readonly class GenericTestCaseFactory implements TestCaseFactory
{
    public function __construct(
        private Storage $storage,
        private TestSuite $testSuite
    ) {}

    /**
     * @return TestCase[]
     *
     * @throws MissingFrontmatterException
     * @throws MissingRequiredKeyException
     * @throws UnrecognizedKeyException
     * @throws UnrecognizedNegativePhaseException
     * @throws ParseException
     */
    public function make(string ...$paths): array
    {
        $testCases = [];
        foreach($paths as $path) {
            $testCases = [...$testCases, ...$this->makeFromSinglePath($path)];
        }

        return $testCases;
    }

    /**
     * @return TestCase[]
     *
     * @throws MissingFrontmatterException
     * @throws MissingRequiredKeyException
     * @throws UnrecognizedKeyException
     * @throws UnrecognizedNegativePhaseException
     * @throws ParseException
     */
    private function makeFromSinglePath(string $path): array
    {
        /**
         * @var string $content
         */
        $content = $this->storage->get($path)
            ?? throw new RuntimeException("Could not open `{$path}`");

        $index = preg_match('/\/\*---(.*)---\*\//s', $content, $match);
        if ($index !== 1) {
            throw new MissingFrontmatterException("Provided test file does not contain a frontmatter section: {$path}");
        }

        $meta = preg_split(
            pattern: '/[\x{000A}\x{000D}\x{2028}\x{2029}]/u',
            subject: $match[$index],
            flags: PREG_SPLIT_NO_EMPTY
        ) ?: [];

        $rawFrontmatter = '';
        if ($line = reset($meta)) {
            $identSize = strlen($line) - strlen(ltrim($line));

            $meta = array_map(static fn(string $line): string => substr($line, $identSize), $meta);
            $rawFrontmatter = implode(PHP_EOL, $meta);
        }

        $frontmatter = new GenericFrontmatter($rawFrontmatter);

        if (in_array(FrontmatterFlag::raw, $frontmatter->flags(), true)) {
            return [new GenericTestCase($path, $content, $frontmatter, $this->testSuite, ImplicitStrictness::Unknown)];
        }

        if (in_array(FrontmatterFlag::module, $frontmatter->flags(), true)) {
            return [new GenericTestCase($path, $content, $frontmatter, $this->testSuite, ImplicitStrictness::Strict)];
        }

        if (in_array(FrontmatterFlag::noStrict, $frontmatter->flags(), true)) {
            return [new GenericTestCase($path, $content, $frontmatter, $this->testSuite, ImplicitStrictness::Loose)];
        }

        if (in_array(FrontmatterFlag::onlyStrict, $frontmatter->flags(), true)) {
            return [new GenericTestCase($path, "\"use strict\";\n{$content}", $frontmatter, $this->testSuite, ImplicitStrictness::Strict)];
        }

        return [
            new GenericTestCase($path, $content, $frontmatter, $this->testSuite, ImplicitStrictness::Loose),
            new GenericTestCase($path, "\"use strict\";\n{$content}", $frontmatter, $this->testSuite, ImplicitStrictness::Strict)
        ];
    }
}
