<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Test;

use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\EcmaScript\Core\Contracts\Values\AbruptCompletion;
use Oru\EcmaScript\Harness\Contracts\Printer;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestResult;
use Oru\EcmaScript\Harness\Contracts\TestResultState;
use Oru\EcmaScript\Harness\Test\Exception\AssertionFailedException;
use RuntimeException;
use Throwable;

use function ini_get_all;

final readonly class ParallelTestRunner extends BaseTestRunner
{
    private string $command;

    public function __construct(
        private Engine $engine,
        private Printer $printer
    ) {
        $this->command = $this->initializeCommand();
    }

    private function initializeCommand(): string
    {
        $iniSettings = ini_get_all(details: false);

        $iniSettingsJson = \str_replace('\\\\', '\\\\\\\\', \json_encode($iniSettings));
        $code = <<<"EOF"
        <?php
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
        $staticClass = static::class;

        $serializedConfig = \serialize($config);
        $serializedConfig = \str_replace('\\', '\\\\', $serializedConfig);
        $serializedConfig = \str_replace('\'', '\\\'', $serializedConfig);

        $code = <<<"EOF"
            <?php

            declare(strict_types=1);

            use Oru\EcmaScript\Core\Contracts\Agent;
            use Oru\EcmaScript\Core\Contracts\Values\AbruptCompletion;
            use Oru\EcmaScript\Core\Contracts\Values\ObjectValue;
            use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
            use Oru\EcmaScript\Core\Contracts\Values\UndefinedValue;
            use Oru\EcmaScript\EngineImplementation;
            use Oru\EcmaScript\Harness\Contracts\TestConfig;
            use Oru\EcmaScript\Harness\Contracts\TestResult;
            use Oru\EcmaScript\Harness\Contracts\TestResultState;
            use {$staticClass};
            use Tests\Test262\Utilities\PrintIntrinsic;
            use Tests\Test262\Utilities\S262Intrinsic;
            
            use function Oru\EcmaScript\Harness\getEngine;
            use function Oru\EcmaScript\Operations\Abstract\get;
            use function Oru\EcmaScript\Operations\Abstract\hasProperty;

            require './vendor/autoload.php';

            echo serialize({$staticClass}::executeTest(getEngine(), unserialize('{$serializedConfig}')));
            EOF;

        $output = $this->runCodeInSeperateProcess($this->command, $code);

        /**
         * @var TestResult $result
         */
        $result = \unserialize($output);

        $this->printer->step($result->state());

        return $result;
    }

    private function runCodeInSeperateProcess(string $command, string $code): string
    {
        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
            1 => ["pipe", "w"],  // stdout is a pipe that the child will write to
            2 => ["pipe", "w"]   // stderr is a pipe that the child will write to
        ];

        $cwd = '.';
        $env = [];

        $options = ['bypass_shell' => true];

        $process = \proc_open($command, $descriptorspec, $pipes, $cwd, $env, $options);

        if (!\is_resource($process)) {
            throw new RuntimeException('Coud not open process');
        }

        // $pipes now looks like this:
        // 0 => writeable handle connected to child stdin
        // 1 => readable handle connected to child stdout
        // 2 => readable handle connected to child stderr

        \fwrite($pipes[0], $code);
        \fclose($pipes[0]);

        $output = \stream_get_contents($pipes[1]);
        $errors = \stream_get_contents($pipes[2]);
        \fclose($pipes[1]);

        // It is important that you close any pipes before calling
        // proc_close in order to avoid a deadlock
        $return_value = \proc_close($process);

        return $output;
    }

    public static function executeTest(Engine $engine, TestConfig $config): TestResult
    {
        $differences = array_diff($config->frontmatter()->features(), $engine->getSupportedFeatures());

        if (count($differences) > 0) {
            return new GenericTestResult(TestResultState::Skip, [], 0);
        }

        foreach ($config->frontmatter()->includes() as $include) {
            $engine->addFiles($include->value);
        }

        $engine->addCode($config->content());

        try {
            $actual = $engine->run();
        } catch (Throwable $throwable) {
            return new GenericTestResult(TestResultState::Error, [], 0, $throwable);
        }

        $result = new GenericTestResult(TestResultState::Success, \get_included_files(), 0);

        try {
            if ($config->frontmatter()->negative()) {
                static::assertFailure($engine->getAgent(), $actual, $config->frontmatter()->negative());
            } else {
                static::assertSuccess($engine->getAgent(), $actual);
            }
        } catch (AssertionFailedException $assertionFailedException) {
            $result = new GenericTestResult(TestResultState::Fail, [], 0, $assertionFailedException);
        }

        return $result;
    }
}
