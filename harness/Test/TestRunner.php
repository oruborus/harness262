<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Test;

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

use function array_diff;
use function count;
use function Oru\EcmaScript\Operations\Abstract\get;
use function Oru\EcmaScript\Operations\Abstract\hasProperty;

final readonly class TestRunner
{
    public function __construct(
        private Printer $printer
    ) {
    }

    public function run(TestConfig $config): TestResult
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
            $this->printer->step(TestResultState::Error);
            return new GenericTestResult(TestResultState::Error, [], 0, $throwable);
        }

        if (isset($config->negative()['type'])) {
            $type = $config->negative()['type'];
            $result = $this->assertThrowCompletionWithErrorConstructorName($engine->getAgent(), $actual, $type);
            $this->printer->step($result->state());
            return $result;
        }

        if ($result = $this->throwIfThrowCompletion($engine->getAgent(), $actual)) {
            $this->printer->step(TestResultState::Fail);
            return new GenericTestResult(TestResultState::Fail, [], 0, $result);
        }
        if ($actual instanceof AbruptCompletion) {
            $this->printer->step(TestResultState::Fail);
            return new GenericTestResult(TestResultState::Fail, [], 0, new RuntimeException('Expected `NormalCompletion`'));
        }

        $this->printer->step(TestResultState::Success);
        return new GenericTestResult(TestResultState::Success, [], 0);
    }

    protected function assertThrowCompletionWithErrorConstructorName(Agent $agent, mixed $completion, string $constructorName): TestResult
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
            if ($result = $this->throwIfThrowCompletion($agent, $object)) {
                return new GenericTestResult(TestResultState::Fail, [], 0, $result);
            }
        }

        $hasName = hasProperty($agent, $object, $factory->createString('name'));
        if ($hasName instanceof AbruptCompletion) {
            if ($result = $this->throwIfThrowCompletion($agent, $hasName)) {
                return new GenericTestResult(TestResultState::Fail, [], 0, $result);
            }
        }

        if (!$hasName->getValue()) {
            $object = get($agent, $object, $factory->createString('constructor'));
            if ($object instanceof AbruptCompletion) {
                if ($result = $this->throwIfThrowCompletion($agent, $object)) {
                    return new GenericTestResult(TestResultState::Fail, [], 0, $result);
                }
            }

            if (!$object instanceof ObjectValue) {
                if ($result = $this->throwIfThrowCompletion($agent, $completion)) {
                    return new GenericTestResult(TestResultState::Fail, [], 0, $result);
                }
            }

            $hasName = hasProperty($agent, $object, $factory->createString('name'));
            if ($hasName instanceof AbruptCompletion) {
                if ($result = $this->throwIfThrowCompletion($agent, $hasName)) {
                    return new GenericTestResult(TestResultState::Fail, [], 0, $result);
                }
            }

            if (!$hasName->getValue()) {
                if ($result = $this->throwIfThrowCompletion($agent, $completion)) {
                    return new GenericTestResult(TestResultState::Fail, [], 0, $result);
                }
            }
        }

        $name = get($agent, $object, $factory->createString('name'));
        if ($name instanceof AbruptCompletion) {
            if ($result = $this->throwIfThrowCompletion($agent, $name)) {
                return new GenericTestResult(TestResultState::Fail, [], 0, $result);
            }
        }

        $name = (string) $name;

        if ($constructorName !== $name) {
            return new GenericTestResult(TestResultState::Fail, [], 0, new RuntimeException("Expected `{$constructorName}` but got `{$name}`"));
        }

        return new GenericTestResult(TestResultState::Success, [], 0);
    }

    protected function throwIfThrowCompletion(Agent $agent, mixed $completion): ?Throwable
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
        if ($result = $this->throwIfThrowCompletion($agent, $message)) {
            return $result;
        }

        if ($message instanceof UndefinedValue) {
            return new RuntimeException("EngineError without message :(");
        }

        return new RuntimeException($message->getValue($agent)->getValue());
    }
}
