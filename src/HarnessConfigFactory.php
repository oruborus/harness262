<?php

declare(strict_types=1);

namespace Oru\Harness;

use FilesystemIterator;
use Iterator;
use Oru\Harness\Contracts\ArgumentsParser;
use Oru\Harness\Contracts\ConfigFactory;
use Oru\Harness\Contracts\OutputConfig;
use Oru\Harness\Contracts\OutputType;
use Oru\Harness\Contracts\PrinterConfig;
use Oru\Harness\Contracts\PrinterVerbosity;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Contracts\TestSuiteConfig;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

use function file_exists;
use function is_dir;
use function is_file;

final readonly class HarnessConfigFactory implements ConfigFactory
{
    public function __construct(
        private ArgumentsParser $argumentsParser
    ) {
    }

    /**
     * @throws RuntimeException
     */
    public function make(): OutputConfig&PrinterConfig&TestSuiteConfig
    {
        $paths = $this->argumentsParser->rest();
        $paths = [];
        foreach ($this->argumentsParser->rest() as $providedPath) {
            if (!file_exists($providedPath)) {
                throw new RuntimeException("Provided path `{$providedPath}` does not exist");
            }

            if (is_file($providedPath)) {
                $paths[] = $providedPath;
            }

            if (is_dir($providedPath)) {
                /**
                 * @var Iterator<string> $it
                 */
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(
                        $providedPath,
                        RecursiveDirectoryIterator::SKIP_DOTS
                            | FilesystemIterator::KEY_AS_PATHNAME
                            | FilesystemIterator::CURRENT_AS_PATHNAME
                            | FilesystemIterator::UNIX_PATHS
                    ),
                );
                foreach ($it as $file) {
                    $paths[] = $file;
                }
            }
        }
        if ($paths === []) {
            throw new RuntimeException('No test path specified. Aborting.');
        }

        $cache = !$this->argumentsParser->hasOption('no-cache');

        $verbosity = PrinterVerbosity::Normal;
        if (
            $this->argumentsParser->hasOption('verbose')
            && !$this->argumentsParser->hasOption('silent')
        ) {
            $verbosity = PrinterVerbosity::Verbose;
        }
        if (
            $this->argumentsParser->hasOption('silent')
            && !$this->argumentsParser->hasOption('verbose')
        ) {
            $verbosity = PrinterVerbosity::Silent;
        }

        $testRunnerMode = TestRunnerMode::Async;

        if ($this->argumentsParser->hasOption('debug')) {
            $testRunnerMode = TestRunnerMode::Linear;
        }

        return new class(
            $paths,
            $cache,
            $testRunnerMode,
            $verbosity
        ) implements OutputConfig, PrinterConfig, TestSuiteConfig
        {
            public function __construct(
                /**
                 * @var string[] $paths
                 */
                private array $paths,
                private bool $cache,
                private TestRunnerMode $testRunnerMode,
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

            public function cache(): bool
            {
                return $this->cache;
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

            public function testRunnerMode(): TestRunnerMode
            {
                return $this->testRunnerMode;
            }
        };
    }
}
