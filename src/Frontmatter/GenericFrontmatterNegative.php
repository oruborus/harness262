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

use Oru\Harness\Contracts\FrontmatterNegative;
use Oru\Harness\Contracts\FrontmatterNegativePhase;
use Oru\Harness\Frontmatter\Exception\UnrecognizedNegativePhaseException;

final readonly class GenericFrontmatterNegative implements FrontmatterNegative
{
    private FrontmatterNegativePhase $phase;

    private string $type;

    /**
     * @param array {
     *     phase: string,
     *     type: string
     * } $rawFrontmatterNegative
     */
    public function __construct(array $rawFrontmatterNegative)
    {
        $this->phase = FrontmatterNegativePhase::tryFrom($rawFrontmatterNegative['phase'])
            ?? throw new UnrecognizedNegativePhaseException("Unrecognized negative phase `{$rawFrontmatterNegative['phase']}`");
        $this->type  = $rawFrontmatterNegative['type'];
    }

    public function phase(): FrontmatterNegativePhase
    {
        return $this->phase;
    }

    public function type(): string
    {
        return $this->type;
    }
}
