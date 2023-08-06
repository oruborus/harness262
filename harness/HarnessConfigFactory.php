<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness;

use Oru\EcmaScript\Harness\Contracts\Config;
use Oru\EcmaScript\Harness\Contracts\ConfigFactory;
use Oru\EcmaScript\Harness\Contracts\OutputConfig;
use Oru\EcmaScript\Harness\Contracts\OutputType;
use Oru\EcmaScript\Harness\Contracts\PrinterConfig;
use Oru\EcmaScript\Harness\Contracts\PrinterVerbosity;
use Oru\EcmaScript\Harness\Contracts\TestSuiteConfig;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_pop;
use function ctype_alpha;
use function explode;
use function implode;
use function in_array;
use function str_split;
use function str_starts_with;
use function strtolower;
use function substr;

final readonly class HarnessConfigFactory implements ConfigFactory
{
    /**
     * @param string[] $input
     */
    public function make(array $input): OutputConfig&PrinterConfig&TestSuiteConfig
    {
        $shortOptions = array_map(
            strtolower(...),
            array_filter(
                str_split(
                    implode(
                        array_filter(
                            $input,
                            static fn (string $option): bool => str_starts_with($option, '-')
                        )
                    )
                ),
                ctype_alpha(...)
            )
        );

        $longOptions = [];
        $filteredOptions = array_filter(
            $input,
            static fn (string $option): bool => str_starts_with($option, '--')
        );

        foreach ($filteredOptions as $option) {
            $values = explode(
                '=',
                substr(
                    strtolower($option),
                    2
                )
            );
            $key = array_pop($values);

            $longOptions[$key] = $values;
        }

        $paths = array_filter(
            $input,
            static fn (string $option): bool => !str_starts_with($option, '-')
        );

        $verbosity = PrinterVerbosity::Normal;
        if (
            (in_array('v', $shortOptions, true) || array_key_exists('verbose', $longOptions))
            && !(in_array('s', $shortOptions, true) || array_key_exists('silent', $longOptions))
        ) {
            $verbosity = PrinterVerbosity::Verbose;
        }
        if (
            (in_array('s', $shortOptions, true) || array_key_exists('silent', $longOptions))
            && !(in_array('v', $shortOptions, true) || array_key_exists('verbose', $longOptions))
        ) {
            $verbosity = PrinterVerbosity::Silent;
        }

        return new class($paths, $verbosity) implements OutputConfig, PrinterConfig, TestSuiteConfig
        {
            public function __construct(
                /**
                 * @var string[] $paths
                 */
                private array $paths,
                private PrinterVerbosity $printerVerbosity
            ) {
            }

            /**
             * @return string[]
             */
            public function paths(): array
            {
                return $this->paths;
            }

            /**
             * @return OutputType[]
             */
            public function outputTypes(): array
            {
                return [OutputType::Console];
            }

            public function verbosity(): PrinterVerbosity
            {
                return $this->printerVerbosity;
            }
        };
    }
}
