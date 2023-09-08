<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Frontmatter;

use Oru\EcmaScript\Harness\Contracts\FrontmatterNegative;
use Oru\EcmaScript\Harness\Contracts\FrontmatterNegativePhase;
use Oru\EcmaScript\Harness\Frontmatter\Exception\UnrecognizedNegativePhaseException;

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
