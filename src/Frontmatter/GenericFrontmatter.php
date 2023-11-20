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

namespace Oru\Harness\Frontmatter;

use Oru\Harness\Contracts\Frontmatter;
use Oru\Harness\Contracts\FrontmatterFlag;
use Oru\Harness\Contracts\FrontmatterInclude;
use Oru\Harness\Contracts\FrontmatterNegative;
use Oru\Harness\Frontmatter\Exception\MissingRequiredKeyException;
use Oru\Harness\Frontmatter\Exception\UnrecognizedFlagException;
use Oru\Harness\Frontmatter\Exception\UnrecognizedIncludeException;
use Oru\Harness\Frontmatter\Exception\UnrecognizedKeyException;
use Oru\Harness\Frontmatter\Exception\UnrecognizedNegativePhaseException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

use function array_diff;
use function array_keys;
use function assert;
use function count;
use function implode;
use function in_array;

/**
 * @see https://github.com/tc39/test262/blob/main/CONTRIBUTING.md#frontmatter
 */
final readonly class GenericFrontmatter implements Frontmatter
{
    private const REQUIRED_KEYS = ['description'];

    private const VALID_KEYS    = ['description', 'esid', 'info', 'negative', 'includes', 'author', 'flags', 'features', 'locale', 'es5id', 'es6id'];

    /**
     * @var array {
     *     description: string,
     *     esid: ?string,
     *     es5id: ?string,
     *     es6id: ?string,
     *     info: ?string,
     *     negative: ?FrontmatterNegative,
     *     includes: FrontmatterInclude[],
     *     author: ?string,
     *     flags: FrontmatterFlag[],
     *     features: ?string[],
     *     locale: ?string[]
     * } $data
     */
    private array $data;

    /**
     * @throws MissingRequiredKeyException
     * @throws UnrecognizedKeyException
     * @throws UnrecognizedNegativePhaseException
     * @throws ParseException
     */
    public function __construct(string $rawFrontmatter)
    {
        /**
         * @var array {
         *     description: string,
         *     esid: ?string,
         *     es5id: ?string,
         *     es6id: ?string,
         *     info: ?string,
         *     includes: ?string[],
         *     negative: array {
         *         phase: string,
         *         type: string
         *     },
         *     author: ?string,
         *     flags: ?string[],
         *     features: ?string[],
         *     locale: ?string[],
         * } $data
         */
        $data = Yaml::parse($rawFrontmatter);
        $keys = array_keys($data);
        $data['flags'] ??= [];
        $data['includes'] ??= [];

        if ($missingRequiredFields = array_diff(static::REQUIRED_KEYS, $keys)) {
            throw new MissingRequiredKeyException('Required frontmatter fields where not provided: ' . implode(', ', $missingRequiredFields));
        }

        if ($unrecognizedFields = array_diff($keys, static::VALID_KEYS)) {
            throw new UnrecognizedKeyException('Unrecognized frontmatter fields where provided: ' . implode(', ', $unrecognizedFields));
        }

        if (isset($data['negative'])) {
            $data['negative'] = new GenericFrontmatterNegative($data['negative']);
        }

        $newIncludes = [];
        foreach ($data['includes'] as $key => $rawInclude) {
            $newIncludes[$key] = FrontmatterInclude::tryFrom(FrontmatterInclude::basePath . $rawInclude)
                ?? throw new UnrecognizedIncludeException("Unrecognized frontmatter include was provided: `{$rawInclude}`");
        }
        $data['includes'] = $newIncludes;

        $newFlags = [];
        foreach ($data['flags'] as $key => $rawFlag) {
            $newFlags[$key] = FrontmatterFlag::tryFrom($rawFlag)
                ?? throw new UnrecognizedFlagException("Unrecognized frontmatter flag was provided: `{$rawFlag}`");
        }
        $data['flags'] = $newFlags;

        /**
         * @see https://github.com/tc39/test262/blob/main/INTERPRETING.md#test262-defined-bindings
         */
        assert(!in_array(FrontmatterFlag::raw, $data['flags']) || count($data['includes']) === 0);

        if (!in_array(FrontmatterFlag::raw, $data['flags'])) {
            if (
                in_array(FrontmatterFlag::async, $data['flags'])
                && !in_array(FrontmatterInclude::doneprintHandle, $data['includes'])
            ) {
                array_unshift($data['includes'], FrontmatterInclude::doneprintHandle);
            }

            if (!in_array(FrontmatterInclude::sta, $data['includes'])) {
                array_unshift($data['includes'], FrontmatterInclude::sta);
            }

            if (!in_array(FrontmatterInclude::assert, $data['includes'])) {
                array_unshift($data['includes'], FrontmatterInclude::assert);
            }
        }

        $this->data = $data;
    }

    public function description(): string
    {
        return $this->data['description'];
    }

    public function esid(): ?string
    {
        return $this->data['esid'] ?? null;
    }

    public function info(): ?string
    {
        return $this->data['info'] ?? null;
    }

    public function negative(): ?FrontmatterNegative
    {
        return $this->data['negative'] ?? null;
    }

    /**
     * @return FrontmatterInclude[]
     */
    public function includes(): array
    {
        return $this->data['includes'] ?? [];
    }

    public function author(): ?string
    {
        return $this->data['author'] ?? null;
    }

    /**
     * @return FrontmatterFlag[]
     */
    public function flags(): array
    {
        return $this->data['flags'] ?? [];
    }

    /**
     * @return string[]
     */
    public function features(): array
    {
        return $this->data['features'] ?? [];
    }

    /**
     * @return string[]
     */
    public function locale(): array
    {
        return $this->data['locale'] ?? [];
    }

    public function es5id(): ?string
    {
        return $this->data['es5id'] ?? null;
    }

    public function es6id(): ?string
    {
        return $this->data['es6id'] ?? null;
    }
}
