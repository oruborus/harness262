<?php

declare(strict_types=1);

namespace Oru\Harness\Config;

use FilesystemIterator;
use Iterator;
use Oru\Harness\Config\Exception\InvalidPathException;
use Oru\Harness\Config\Exception\MalformedRegularExpressionPatternException;
use Oru\Harness\Config\Exception\MissingPathException;
use Oru\Harness\Contracts\ArgumentsParser;
use Oru\Harness\Contracts\ConfigFactory;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Contracts\TestSuiteConfig;
use Oru\Harness\Helpers\ErrorHandler;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function file_exists;
use function is_dir;
use function is_file;
use function preg_grep;
use function preg_match;
use function strlen;
use function substr;

use const E_WARNING;
use const PREG_GREP_INVERT;

final readonly class TestSuiteConfigFactory implements ConfigFactory
{
    public function __construct(
        private ArgumentsParser $argumentsParser
    ) {
    }

    /**
     * @throws InvalidPathException
     * @throws MalformedRegularExpressionPatternException
     * @throws MissingPathException
     */
    public function make(): TestSuiteConfig
    {
        $paths = $this->argumentsParser->rest();
        $paths = [];
        foreach ($this->argumentsParser->rest() as $providedPath) {
            if (!file_exists($providedPath)) {
                throw new InvalidPathException("Provided path `{$providedPath}` does not exist");
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

        if ($this->argumentsParser->hasOption('include')) {
            $pattern = "/{$this->argumentsParser->getOption('include')}/";

            $this->testRegularExpressionPattern($pattern);

            $paths = preg_grep($pattern, $paths);
        }

        if ($this->argumentsParser->hasOption('exclude')) {
            $pattern = "/{$this->argumentsParser->getOption('exclude')}/";

            $this->testRegularExpressionPattern($pattern);

            $paths = preg_grep($pattern, $paths, PREG_GREP_INVERT);
        }

        if ($paths === []) {
            throw new MissingPathException('No test path specified. Aborting.');
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

            public function concurrency(): int
            {
                return 8;
            }
        };
    }

    private const WARNING_PREFIX = 'preg_match(): ';

    /**
     * @throws MalformedRegularExpressionPatternException
     */
    private function testRegularExpressionPattern(string $pattern): void
    {
        $_ = new ErrorHandler(static function (int $_, string $message): never {
            throw new MalformedRegularExpressionPatternException(substr($message, strlen(static::WARNING_PREFIX)));
        }, E_WARNING);

        /** @psalm-suppress ArgumentTypeCoercion  The next line will warn about any issue with the provided arguments */
        preg_match($pattern, '');
    }
}
