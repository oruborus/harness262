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

namespace Oru\Harness\Contracts;

interface Frontmatter
{
    public function description(): string;

    public function esid(): ?string;

    public function info(): ?string;

    public function negative(): ?FrontmatterNegative;

    /**
     * @return FrontmatterInclude[]
     */
    public function includes(): array;

    public function author(): ?string;

    /**
     * @return FrontmatterFlag[]
     */
    public function flags(): array;

    /**
     * @return string[]
     */
    public function features(): array;

    /**
     * @return string[]
     */
    public function locale(): array;

    public function es5id(): ?string;

    public function es6id(): ?string;
}
