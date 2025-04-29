<?php

/**
 * Copyright (c) 2023-2025, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Tests\Acceptance\Bootstrap;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Generator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use Tests\Acceptance\Bootstrap\Utility\NamedTemporaryFileHelper;
use Tests\Acceptance\Bootstrap\Utility\TemporaryDirectoryHelper;
use Tests\Utility\Engine\Exception\ObjectIdExtractionException;
use Tests\Utility\Engine\Exception\PidExtractionException;

use function array_unique;
use function array_unshift;
use function fclose;
use function implode;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function proc_close;
use function proc_get_status;
use function proc_open;
use function str_replace;
use function str_split;
use function stream_get_contents;
use function strlen;
use function sort;
use function substr;

use const PREG_OFFSET_CAPTURE;

final class FeatureContext implements Context
{
    private string $actual = '';

    private int $lastProcessId = 0;

    private array $temporaries = [];

    #[Given('a directory named :name')]
    public function aDirectoryNamed(string $name): void
    {
        array_unshift($this->temporaries, new TemporaryDirectoryHelper($name));
    }

    #[Given('a file named :name with:')]
    public function aFileNamedWith(string $name, string $data): void
    {
        array_unshift($this->temporaries, new NamedTemporaryFileHelper($name, $data));
    }

    #[Given('an Engine')]
    public function anEngine(): void
    {
        $content = <<<EOF
        <?php

        declare(strict_types=1);
        
        require './vendor/autoload.php';

        return new \Tests\Utility\Engine\TestEngine();
        EOF;

        $this->aFileNamedWith('Harness.php', $content);
    }

    /**
     * @throws Exception
     * @throws ExpectationFailedException
     */
    #[When('I run :command')]
    public function iRun(string $command): void
    {
        $process = proc_open($command, [1 => ["pipe", "w"]], $pipes, options: ['bypass_shell' => true]);

        $this->lastProcessId = proc_get_status($process)['pid'];

        $actual = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        proc_close($process);

        Assert::assertIsString($actual);

        // NOTE: Normalize line ending sequence to <LF>, as the actual is
        //       captured via the shell in behat which might be using <cr><lf>.
        $this->actual = str_replace("\r\n", "\n", $actual);
    }

    /**
     * @throws Exception
     * @throws ExpectationFailedException
     */
    #[Then('I should see:')]
    public function iShouldSee(string $format): void
    {
        Assert::assertStringMatchesFormat($format, $this->actual);
    }

    /**
     * @throws Exception
     * @throws ExpectationFailedException
     */
    #[Then('it should pass with unordered steps:')]
    public function itShouldPassWithUnorderedSteps(string $format): void
    {
        $matchResult = preg_match_all('/%p\(([^\)]*)\)/', $format, $matches, PREG_OFFSET_CAPTURE);
        if (!$matchResult) {
            $this->iShouldSee($format);
        }

        $offset = 0;
        $parts = [];
        for ($i = 1; $i <= $matchResult; $i++) {
            /**
             * @var string $match
             * @var int $newOffset
             */
            [[$match, $newOffset]] = $matches[$i];
            $matchLength = strlen($match);
            $parts[] = substr($this->actual, $offset, $newOffset - $offset - 3);
            $offset = $newOffset + $matchLength - 3;
            $actual = substr($this->actual, $newOffset - 3, $matchLength);

            $matchOrdered = $this->orderCharacters($match);
            $actualOrdered = $this->orderCharacters($actual);

            if ($matchOrdered !== $actualOrdered) {
                $this->iShouldSee($format);
            }

            $found = false;
            foreach ($this->generatePermutations($match) as $permutation) {
                if ($actual === $permutation) {
                    $parts[] = $permutation;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $this->iShouldSee($format);
            }
        }

        $parts[] = substr($this->actual, $offset);
        $format = implode($parts);
        $this->iShouldSee($format);
    }

    private function orderCharacters(string $string): string
    {
        $string = str_split($string);
        sort($string);

        return implode($string);
    }

    /**
     * @return Generator<int, string>
     */
    private function generatePermutations(string $string, int $start = 0, ?int $end = null): Generator
    {
        if ($end === null) {
            $end = strlen($string) - 1;
        }

        if ($start === $end) {
            yield $string;
        }

        for ($i = $start; $i <= $end; $i++) {
            [$string[$start], $string[$i]] = [$string[$i], $string[$start]];
            yield from $this->generatePermutations($string, $start + 1, $end);
            [$string[$start], $string[$i]] = [$string[$i], $string[$start]];
        }
    }

    /**
     * @throws Exception
     * @throws ExpectationFailedException
     */
    #[Then('a new process gets spawned')]
    public function aNewProcessGetsSpawned(): void
    {
        $pattern = '/' . preg_quote(PidExtractionException::class, '/') . ': (?<pid>[+-]?\d+)/';
        $found = preg_match($pattern, $this->actual, $matches);

        Assert::assertSame(1, $found, 'Could not extract PID from child process');
        Assert::assertNotSame($this->lastProcessId, (int) $matches['pid'], 'Could not detect the creation of another process');
    }

    /**
     * @throws Exception
     * @throws ExpectationFailedException
     */
    #[Then('a new engine is used')]
    public function aNewEngineIsUsed(): void
    {
        $pattern = '/' . preg_quote(ObjectIdExtractionException::class, '/') . ': (?<oid>[+-]?\d+)/';
        $found = preg_match_all($pattern, $this->actual, $matches);

        Assert::assertGreaterThan(1, $found, 'Could not extract more than one engine object id');
        Assert::assertSame($matches['oid'], array_unique($matches['oid']), 'Could not verify the use of a new engine for each test case as the list of engine object ids is not unique');
    }
}
