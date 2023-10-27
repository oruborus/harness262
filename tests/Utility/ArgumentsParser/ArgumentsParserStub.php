<?php

declare(strict_types=1);

namespace Tests\Utility\ArgumentsParser;

use Oru\Harness\Contracts\ArgumentsParser;

use function array_key_exists;

final class ArgumentsParserStub implements ArgumentsParser
{
    /**
     * @param array<string, ?string> $options
     * @param list<string> $rest
     */
    public function __construct(
        private array $options = [],
        private array $rest = []
    ) {
    }

    public function hasOption(string $option): bool
    {
        return array_key_exists($option, $this->options);
    }

    public function getOption(string $option): string
    {
        return $this->options[$option] ?? '';
    }

    public function rest(): array
    {
        return $this->rest;
    }
}
