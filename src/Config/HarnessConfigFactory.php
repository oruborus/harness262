<?php

declare(strict_types=1);

namespace Oru\Harness\Config;

use FilesystemIterator;
use Iterator;
use Oru\Harness\Contracts\ArgumentsParser;
use Oru\Harness\Contracts\ConfigFactory;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Contracts\TestSuiteConfig;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

use function array_filter;
use function file_exists;
use function is_dir;
use function is_file;
use function preg_match;

final readonly class HarnessConfigFactory implements ConfigFactory
{
    public function __construct(
        private ArgumentsParser $argumentsParser
    ) {
    }

    /**
     * @throws RuntimeException
     */
    public function make(): TestSuiteConfig
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

        if ($this->argumentsParser->hasOption('filter')) {
            $pattern = "/{$this->argumentsParser->getOption('filter')}/";
            $paths = array_filter($paths, static fn (string $path): bool => (bool) preg_match($pattern, $path));
        }

        if ($paths === []) {
            throw new RuntimeException('No test path specified. Aborting.');
        }

        $cache = !$this->argumentsParser->hasOption('no-cache');

        $testRunnerMode = TestRunnerMode::Async;

        if ($this->argumentsParser->hasOption('debug')) {
            $testRunnerMode = TestRunnerMode::Linear;
        }

        return new class(
            $paths,
            $cache,
            $testRunnerMode
        ) implements TestSuiteConfig
        {
            public function __construct(
                /**
                 * @var string[] $paths
                 */
                private array $paths,
                private bool $cache,
                private TestRunnerMode $testRunnerMode,
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

            public function testRunnerMode(): TestRunnerMode
            {
                return $this->testRunnerMode;
            }
        };
    }
}
