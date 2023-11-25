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
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestConfigFactory;
use Oru\Harness\Contracts\FrontmatterFlag;
use Oru\Harness\Contracts\ImplicitStrictness;
use Oru\Harness\Contracts\TestSuiteConfig;
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

final readonly class GenericTestConfigFactory implements TestConfigFactory
{
    public function __construct(
        private Storage $storage,
        private TestSuiteConfig $testSuiteConfig
    ) {}

    /**
     * @return TestConfig[]
     *
     * @throws MissingFrontmatterException
     * @throws MissingRequiredKeyException
     * @throws UnrecognizedKeyException
     * @throws UnrecognizedNegativePhaseException
     * @throws ParseException
     */
    public function make(string ...$paths): array
    {
        $testConfigs = [];
        foreach($paths as $path) {
            $testConfigs = [...$testConfigs, ...$this->makeFromSinglePath($path)];
        }

        return $testConfigs;
    }

    /**
     * @return TestConfig[]
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
            return [new GenericTestConfig($path, $content, $frontmatter, $this->testSuiteConfig, ImplicitStrictness::Unknown)];
        }

        if (in_array(FrontmatterFlag::module, $frontmatter->flags(), true)) {
            return [new GenericTestConfig($path, $content, $frontmatter, $this->testSuiteConfig, ImplicitStrictness::Strict)];
        }

        if (in_array(FrontmatterFlag::noStrict, $frontmatter->flags(), true)) {
            return [new GenericTestConfig($path, $content, $frontmatter, $this->testSuiteConfig, ImplicitStrictness::Loose)];
        }

        if (in_array(FrontmatterFlag::onlyStrict, $frontmatter->flags(), true)) {
            return [new GenericTestConfig($path, "\"use strict\";\n{$content}", $frontmatter, $this->testSuiteConfig, ImplicitStrictness::Strict)];
        }

        return [
            new GenericTestConfig($path, $content, $frontmatter, $this->testSuiteConfig, ImplicitStrictness::Loose),
            new GenericTestConfig($path, "\"use strict\";\n{$content}", $frontmatter, $this->testSuiteConfig, ImplicitStrictness::Strict)
        ];
    }
}
