<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Test;

use Fiber;
use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\EcmaScript\Harness\Contracts\AssertionFactory;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestResult;
use Oru\EcmaScript\Harness\Contracts\TestResultState;
use Oru\EcmaScript\Harness\Contracts\TestRunner;
use RuntimeException;
use Throwable;

use function fclose;
use function fopen;
use function fwrite;
use function ini_get_all;
use function json_decode;
use function json_encode;
use function proc_close;
use function proc_get_status;
use function proc_open;
use function realpath;
use function rewind;
use function serialize;
use function str_replace;
use function stream_get_contents;
use function unserialize;

use const JSON_OBJECT_AS_ARRAY;
use const JSON_THROW_ON_ERROR;

final readonly class AsyncTestRunner implements TestRunner
{
    private string $command;

    public function __construct(
        private Engine $engine,
        private AssertionFactory $assertionFactory
    ) {
        $this->command = $this->initializeCommand();
    }

    private function initializeCommand(): string
    {
        $iniSettings = ini_get_all(details: false);

        $iniSettingsJson = str_replace('\\\\', '\\\\\\\\', json_encode($iniSettings));
        $code = <<<"EOF"
            <?php

            declare(strict_types=1);

            \$ini   = ini_get_all(details: false);
            \$given = json_decode('{$iniSettingsJson}', true, flags: JSON_OBJECT_AS_ARRAY|JSON_THROW_ON_ERROR);
            \$diff  = array_diff_assoc(\$given, \$ini);

            echo str_replace('\\\\', '\\\\\\\\', json_encode(\$diff));
            EOF;

        $output = $this->runCodeInSeperateProcess('php', $code);
        $output = json_decode($output, true, flags: JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);

        $command = 'php ';
        foreach ($output as $entry => $setting) {
            $command .= "-d \"{$entry}={$setting}\" ";
        }

        return $command;
    }

    public function command(): string
    {
        return $this->command;
    }

    public function run(TestConfig $config): TestResult
    {
        $command = $this->command;

        $loop = Loop::get();

        $task = static function () use ($command, $loop, $config): void {
            $serializedConfig = serialize($config);

            $stdout = fopen('php://temporary', 'w+');
            $stderr = fopen('php://temporary', 'w+');

            $descriptorspec = [
                0 => ["pipe", "r"],
                1 => $stdout,
                2 => $stderr
            ];

            $cwd = '.';
            $env = [];

            $options = ['bypass_shell' => true];

            $process = proc_open($command . realpath('./harness/Template/ExecuteTest.php'), $descriptorspec, $pipes, $cwd, $env, $options)
                ?: throw new RuntimeException('Coud not open process');

            fwrite($pipes[0], $serializedConfig);
            fclose($pipes[0]);

            while (proc_get_status($process)['running']) {
                Fiber::suspend();
            }

            rewind($stdout);
            $output = stream_get_contents($stdout);
            fclose($stdout);

            rewind($stderr);
            $errors = stream_get_contents($stderr);
            fclose($stderr);

            $return_value = proc_close($process);

            /**
             * @var TestResult $result
             */
            $result = unserialize($output);

            if ($result instanceof Throwable) {
                throw $result;
            }

            $loop->addResult($result);
        };

        $loop->add($task);

        return new GenericTestResult(TestResultState::Pending, [], 0);
    }

    private function runCodeInSeperateProcess(string $command, string $code): string
    {
        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $cwd = '.';
        $env = [];

        $options = ['bypass_shell' => true];

        $process = proc_open($command, $descriptorspec, $pipes, $cwd, $env, $options)
            ?: throw new RuntimeException('Coud not open process');

        fwrite($pipes[0], $code);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $return_value = proc_close($process);

        return $output;
    }
}
