<?php

declare(strict_types=1);

namespace Oru\Harness\Cli;

use Oru\Harness\Cli\Exception\InvalidOptionException;
use Oru\Harness\Cli\Exception\UnknownOptionException;
use Oru\Harness\Contracts\ArgumentsParser;

use function array_key_exists;
use function array_search;
use function array_values;
use function strlen;
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

                $this->options[$longOption] = null;
                continue;
            }

            // Expand short options in the form of `-abc`
            $multipleShortOptions = str_split(substr($argument, 1));
            foreach ($multipleShortOptions as $shortOption) {
                $longOption = array_search($shortOption, $configuration, true);
                if ($longOption === false) {
                    throw new UnknownOptionException("Unknown short option `{$shortOption}` provided");
                }

                $this->options[$longOption] = null;
            }
        }
    }

    public function hasOption(string $option): bool
    {
        return array_key_exists($option, $this->options);
    }

    /**
     * @return string[]
     */
    public function rest(): array
    {
        return $this->rest;
    }
}
