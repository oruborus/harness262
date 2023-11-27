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

namespace Oru\Harness\TestSuite;

use FilesystemIterator;
use Iterator;
use Oru\Harness\Contracts\ArgumentsParser;
use Oru\Harness\Contracts\CoreCounter;
use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Contracts\TestSuite;
use Oru\Harness\TestSuite\Exception\InvalidPathException;
use Oru\Harness\TestSuite\Exception\MissingPathException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function file_exists;
use function is_dir;
use function is_file;
use function max;
use function min;

final readonly class TestSuiteFactory
{
    public function __construct(
        private ArgumentsParser $argumentsParser,
        private CoreCounter $coreCounter,
    ) {}

    /**
     * @throws InvalidPathException
     * @throws MissingPathException
     */
    public function make(): TestSuite
    {
        $paths = $this->argumentsParser->rest();
        $paths = [];
        foreach ($this->argumentsParser->rest() as $providedPath) {
            if (!file_exists($providedPath)) {
                throw new InvalidPathException("Provided path `{$providedPath}` does not exist");
            }

            if (is_file($providedPath)) {
                if (!str_contains(basename($providedPath), '_FIXTURE')) {
                    $paths[] = $providedPath;
                }
                continue;
            }

            if (is_dir($providedPath)) {
                /**
                 * @var Iterator<string, string> $it
                 */
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(
                        $providedPath,
                        RecursiveDirectoryIterator::SKIP_DOTS
                            | FilesystemIterator::KEY_AS_FILENAME
                            | FilesystemIterator::CURRENT_AS_PATHNAME
                            | FilesystemIterator::UNIX_PATHS
                    ),
                );
                foreach ($it as $filename => $path) {
                    if (!str_contains($filename, '_FIXTURE')) {
                        $paths[] = $path;
                    }
                }
            }
        }

        if ($paths === []) {
            throw new MissingPathException('No test path specified. Aborting.');
        }

        $cache = !$this->argumentsParser->hasOption('no-cache');

        $testRunnerMode = TestRunnerMode::Async;

        if ($this->argumentsParser->hasOption('debug')) {
            $testRunnerMode = TestRunnerMode::Linear;
        }

        $stopOnCharacteristic = StopOnCharacteristic::Nothing;
        if ($this->argumentsParser->hasOption('stop-on-error')) {
            $stopOnCharacteristic = StopOnCharacteristic::Error;
        }
        if ($this->argumentsParser->hasOption('stop-on-failure')) {
            $stopOnCharacteristic = StopOnCharacteristic::Failure;
        }
        if (
            $this->argumentsParser->hasOption('stop-on-error') && $this->argumentsParser->hasOption('stop-on-failure')
            || $this->argumentsParser->hasOption('stop-on-defect')
        ) {
            $stopOnCharacteristic = StopOnCharacteristic::Defect;
        }

        $concurrency = $this->coreCounter->count();
        if ($this->argumentsParser->hasOption('concurrency')) {
            $concurrency = max(1, min((int) $this->argumentsParser->getOption('concurrency'), $concurrency));
        }

        return new GenericTestSuite(
            $paths,
            $cache,
            $concurrency,
            $testRunnerMode,
            $stopOnCharacteristic
        );
    }
}
