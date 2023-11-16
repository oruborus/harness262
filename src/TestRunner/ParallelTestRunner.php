<?php

declare(strict_types=1);

namespace Oru\Harness\TestRunner;

use Fiber;
use Oru\Harness\Contracts\AssertionFactory;
use Oru\Harness\Contracts\Command;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestRunner;
use RuntimeException;
use Throwable;

use function assert;
use function fclose;
use function fwrite;
use function proc_close;
use function proc_open;
use function serialize;
use function stream_get_contents;
use function unserialize;

final class ParallelTestRunner implements TestRunner
{
    private bool $dirty = true;

    /**
     * @var TestConfig[] $configs
     */
    private array $configs = [];

    /**
     * @var TestResult[] $results
     */
    private array $results = [];

    public function __construct(
        private readonly AssertionFactory $assertionFactory,
        private readonly Printer $printer,
        private readonly Command $command
    ) {
    }

    /**
     * @throws Throwable
     * @throws RuntimeException
     */
    public function add(TestConfig $config): void
    {
        $this->dirty = true;
        $this->configs[] = $config;
    }

    /**
     * @return TestResult[]
     */
    public function run(): array
    {
        if (!$this->dirty) {
            return $this->results;
        }

        foreach ($this->configs as $config) {
            $this->dirty = false;
            $serializedConfig = serialize($config);

            $descriptorspec = [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["pipe", "w"]
            ];

            $cwd = '.';
            $env = [];

            $options = ['bypass_shell' => true];

            $process = @proc_open((string) $this->command, $descriptorspec, $pipes, $cwd, $env, $options)
                ?: throw new RuntimeException('Could not open process');

            fwrite($pipes[0], $serializedConfig);
            fclose($pipes[0]);

            if (Fiber::getCurrent()) {
                while (proc_get_status($process)['running']) {
                    Fiber::suspend();
                }
            }

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            $exitCode = proc_close($process);
            assert($exitCode === 0, $output);

            $result = unserialize($output);

            if ($result instanceof Throwable) {
                throw $result;
            }

            if (!$result instanceof TestResult) {
                throw new RuntimeException("Subprocess did not return a `TestResult` - Returned: {$output}");
            }

            $this->printer->step($result->state());
            $this->results[] = $result;
        }

        return $this->results;
    }

    /**
     * @return TestResult[]
     */
    public function results(): array
    {
        return $this->results;
    }
}
