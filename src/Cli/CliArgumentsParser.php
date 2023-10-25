<?php

declare(strict_types=1);

namespace Oru\Harness\Cli;

use Oru\Harness\Cli\Exception\InvalidOptionException;
use Oru\Harness\Cli\Exception\MissingArgumentException;
use Oru\Harness\Cli\Exception\UnknownOptionException;
use Oru\Harness\Contracts\ArgumentsParser;

use function array_key_exists;
use function array_search;
use function is_null;
use function str_contains;
use function str_starts_with;
use function substr;

final class CliArgumentsParser implements ArgumentsParser
{
    /**
     * @var array<string, ?string> $options
     */
    private array $options = [];

    /**
     * @var string[] $rest
     */
    private array $rest = [];

    /**
     * @param list<string> $arguments
     * @param array<string, ?string> $configuration
     * 
     * @throws InvalidOptionException
     * @throws UnknownOptionException
     */
    public function __construct(
        array $arguments,
        array $configuration
    ) {
        // Map short options to long options
        for ($index = 0; $index < count($arguments); $index++) {
            $argument = $arguments[$index];

            if ($argument === '-' || $argument === '--') {
                throw new InvalidOptionException("Invalid option `{$argument}` provided");
            }

            // Neither a short nor a long option
            if ($argument[0] !== '-') {
                $this->rest[] = $argument;
                continue;
            }

            // Long option
            if ($argument[1] === '-') {
                $longOption = substr($argument, 2);
                $longOptionExists = array_key_exists($longOption, $configuration);
                if ($longOptionExists === false) {
                    throw new UnknownOptionException("Unknown long option `{$longOption}` provided");
                }

                $value = null;
                if (!is_null($configuration[$longOption]) && str_contains($configuration[$longOption], ':')) {
                    if (!isset($arguments[$index + 1])) {
                        throw new MissingArgumentException("Missing argument for option `{$longOption}`");
                    }

                    if (str_starts_with($arguments[$index + 1], '-')) {
                        throw new MissingArgumentException("Missing argument for option `{$longOption}`");
                    }

                    $value = $arguments[$index + 1];
                    $index++;
                }

                $this->options[$longOption] = $value;
                continue;
            }

            // Expand short options in the form of `-abc`
            $multipleShortOptions = str_split(substr($argument, 1));
            foreach ($multipleShortOptions as $shortOption) {
                $longOption = array_search($shortOption, $configuration, true);
                if ($longOption === false) {
                    throw new UnknownOptionException("Unknown short option `{$shortOption}` provided");
                }

                // FIXME: Throw if expaned option requires an argument

                $this->options[$longOption] = null;
            }
        }
    }

    public function hasOption(string $option): bool
    {
        return array_key_exists($option, $this->options);
    }

    /**
     * @throws UnknownOptionException
     * @throws MissingArgumentException
     */
    public function getOption(string $option): string
    {
        if (!array_key_exists($option, $this->options)) {
            throw new UnknownOptionException("Unknown option `{$option}` requested");
        }

        if (is_null($this->options[$option])) {
            throw new MissingArgumentException("Argument for `{$option}` was not provided");
        }

        return $this->options[$option];
    }

    /**
     * @return string[]
     */
    public function rest(): array
    {
        return $this->rest;
    }
}
