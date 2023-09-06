<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Test;

use Fiber;
use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\EcmaScript\Harness\Contracts\AssertionFactory;
use Oru\EcmaScript\Harness\Contracts\Command;
use Oru\EcmaScript\Harness\Contracts\Printer;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestResult;
use Oru\EcmaScript\Harness\Contracts\TestRunner;
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
    /**
     * @var TestResult[] $results
     */
    private array $results = [];

    public function __construct(
        private readonly Engine $engine,
        private readonly AssertionFactory $assertionFactory,
        private readonly Printer $printer,
        private readonly Command $command
    ) {
    }

    public function run(TestConfig $config): void
    {
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
        assert($exitCode === 0);

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

    /**
     * @return TestResult[]
     */
    public function finalize(): array
    {
        return $this->results;
    }
}
