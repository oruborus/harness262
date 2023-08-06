<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Test;

use Fiber;
use Oru\EcmaScript\Core\Contracts\Agent;
use Oru\EcmaScript\Core\Contracts\Values\AbruptCompletion;
use Oru\EcmaScript\Core\Contracts\Values\ObjectValue;
use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
use Oru\EcmaScript\Core\Contracts\Values\UndefinedValue;
use Oru\EcmaScript\EngineImplementation;
use Oru\EcmaScript\Harness\Contracts\Printer;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestResult;
use Oru\EcmaScript\Harness\Contracts\TestResultState;
use RuntimeException;
use Tests\Test262\Utilities\PrintIntrinsic;
use Tests\Test262\Utilities\S262Intrinsic;
use Throwable;

use function Oru\EcmaScript\Operations\Abstract\get;
use function Oru\EcmaScript\Operations\Abstract\hasProperty;

final class ParallelTestRunner
{
    private readonly string $command;

    public function __construct(
        private readonly Printer $printer
    ) {
        $iniSettings = \ini_get_all(null, false)
            ?: throw new RuntimeException('Could not get ini settings');

        $iniSettingsJson = \str_replace('\\\\', '\\\\\\\\', \json_encode($iniSettings));
        $code = <<<"EOF"
        <?php
            \$ini = ini_get_all(null, false)
                ?: throw new RuntimeException('Could not get ini settings from child script');
            \$given = json_decode('{$iniSettingsJson}', true, 2147483647, JSON_OBJECT_AS_ARRAY|JSON_THROW_ON_ERROR);
            \$diff = array_diff_assoc(\$given, \$ini);

            echo str_replace('\\\\', '\\\\\\\\', json_encode(\$diff));
        EOF;

        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
            1 => ["pipe", "w"],  // stdout is a pipe that the child will write to
            2 => ["pipe", "w"]   // stderr is a pipe that the child will write to
        ];

        $cwd = '.';
        $env = [];

        $options = ['bypass_shell' => true];

        $process = \proc_open('php', $descriptorspec, $pipes, $cwd, $env, $options);

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

        $output = json_decode($output, true, 2147483647, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);

        $command = 'php ';
        foreach ($output as $entry => $setting) {
            $command .= "-d \"{$entry}={$setting}\" ";
        }

        $this->command = $command;
    }

    public function run(TestConfig $config): TestResult
    {
        $staticClass = static::class;

        $basicCode = <<<"EOF"
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
            
            use function Oru\EcmaScript\Operations\Abstract\get;
            use function Oru\EcmaScript\Operations\Abstract\hasProperty;

            require './vendor/autoload.php';


            EOF;

        $serializedConfig = \serialize($config);

        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
            1 => ["pipe", "w"],  // stdout is a pipe that the child will write to
            2 => ["pipe", "w"]   // stderr is a pipe that the child will write to
        ];

        $cwd = '.';
        $env = [];

        $options = ['bypass_shell' => true];

        $process = \proc_open($this->command, $descriptorspec, $pipes, $cwd, $env, $options);

        if (!\is_resource($process)) {
            throw new RuntimeException('Coud not open process');
        }

        // $pipes now looks like this:
        // 0 => writeable handle connected to child stdin
        // 1 => readable handle connected to child stdout
        // 2 => readable handle connected to child stderr

        // echo $serializedConfig;
        $serializedConfig = \str_replace('\\', '\\\\', $serializedConfig);
        $serializedConfig = \str_replace('\'', '\\\'', $serializedConfig);

        \fwrite($pipes[0], "{$basicCode} echo serialize({$staticClass}::executeTest(unserialize('{$serializedConfig}')));");
        \fclose($pipes[0]);

        $output = \stream_get_contents($pipes[1]);
        $errors = \stream_get_contents($pipes[2]);
        \fclose($pipes[1]);

        // It is important that you close any pipes before calling
        // proc_close in order to avoid a deadlock
        $return_value = \proc_close($process);

        /**
         * @var TestResult $result
         */
        $result = \unserialize($output);

        $this->printer->step($result->state());

        return $result;
    }

    public static function executeTest(TestConfig $config): TestResult
    {
        $differences = array_diff($config->features(), EngineImplementation::getSupportedFeatures());

        if (count($differences) > 0) {
            return new GenericTestResult(TestResultState::Skip, [], 0);
        }

        $engine = new EngineImplementation(
            hostDefinedProperties: [
                '$262' => S262Intrinsic::class,
                'print' => PrintIntrinsic::class
            ]
        );

        if (count($config->includes()) > 0) {
            $engine->addFiles(...$config->includes());
        }

        $engine->addCode($config->content());

        try {
            $actual = $engine->run();
        } catch (Throwable $throwable) {
            return new GenericTestResult(TestResultState::Error, [], 0, $throwable);
        }

        if (isset($config->negative()['type'])) {
            $type = $config->negative()['type'];
            return static::assertThrowCompletionWithErrorConstructorName($engine->getAgent(), $actual, $type);
        }

        if ($result = static::throwIfThrowCompletion($engine->getAgent(), $actual)) {
            return new GenericTestResult(TestResultState::Fail, [], 0, $result);
        }
        if ($actual instanceof AbruptCompletion) {
            return new GenericTestResult(TestResultState::Fail, [], 0, new RuntimeException('Expected `NormalCompletion`'));
        }

        return new GenericTestResult(TestResultState::Success, \get_included_files(), 0);
    }

    public static function assertThrowCompletionWithErrorConstructorName(Agent $agent, mixed $completion, string $constructorName): TestResult
    {
        $factory = $agent->getInterpreter()->getValueFactory();

        if (!$completion instanceof ThrowCompletion) {
            return new GenericTestResult(TestResultState::Fail, [], 0, 'Expected `ThrowCompletion`');
        }

        /**
         * @var LanguageValue $object
         */
        $object = $completion->getValue();

        if (!$object instanceof ObjectValue) {
            if ($result = static::throwIfThrowCompletion($agent, $object)) {
                return new GenericTestResult(TestResultState::Fail, [], 0, $result);
            }
        }

        $hasName = hasProperty($agent, $object, $factory->createString('name'));
        if ($hasName instanceof AbruptCompletion) {
            if ($result = static::throwIfThrowCompletion($agent, $hasName)) {
                return new GenericTestResult(TestResultState::Fail, [], 0, $result);
            }
        }

        if (!$hasName->getValue()) {
            $object = get($agent, $object, $factory->createString('constructor'));
            if ($object instanceof AbruptCompletion) {
                if ($result = static::throwIfThrowCompletion($agent, $object)) {
                    return new GenericTestResult(TestResultState::Fail, [], 0, $result);
                }
            }

            if (!$object instanceof ObjectValue) {
                if ($result = static::throwIfThrowCompletion($agent, $completion)) {
                    return new GenericTestResult(TestResultState::Fail, [], 0, $result);
                }
            }

            $hasName = hasProperty($agent, $object, $factory->createString('name'));
            if ($hasName instanceof AbruptCompletion) {
                if ($result = static::throwIfThrowCompletion($agent, $hasName)) {
                    return new GenericTestResult(TestResultState::Fail, [], 0, $result);
                }
            }

            if (!$hasName->getValue()) {
                if ($result = static::throwIfThrowCompletion($agent, $completion)) {
                    return new GenericTestResult(TestResultState::Fail, [], 0, $result);
                }
            }
        }

        $name = get($agent, $object, $factory->createString('name'));
        if ($name instanceof AbruptCompletion) {
            if ($result = static::throwIfThrowCompletion($agent, $name)) {
                return new GenericTestResult(TestResultState::Fail, [], 0, $result);
            }
        }

        $name = (string) $name;

        if ($constructorName !== $name) {
            return new GenericTestResult(TestResultState::Fail, [], 0, new RuntimeException("Expected `{$constructorName}` but got `{$name}`"));
        }

        return new GenericTestResult(TestResultState::Success, [], 0);
    }

    public static function throwIfThrowCompletion(Agent $agent, mixed $completion): ?Throwable
    {
        $factory = $agent->getInterpreter()->getValueFactory();

        if (!$completion instanceof ThrowCompletion) {
            return null;
        }

        $value = $completion->getValue();
        if (!$value instanceof ObjectValue) {
            return new RuntimeException((string) $value->getValue());
        }

        $message = $value->getOwnProperty($agent, $factory->createString('message'));
        if ($result = static::throwIfThrowCompletion($agent, $message)) {
            return $result;
        }

        if ($message instanceof UndefinedValue) {
            return new RuntimeException("EngineError without message :(");
        }

        return new RuntimeException($message->getValue($agent)->getValue());
    }
}
