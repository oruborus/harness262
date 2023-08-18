<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Test;

use Oru\EcmaScript\Core\Contracts\Agent;
use Oru\EcmaScript\Core\Contracts\Values\AbruptCompletion;
use Oru\EcmaScript\Core\Contracts\Values\ObjectValue;
use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
use Oru\EcmaScript\Core\Contracts\Values\UndefinedValue;
use Oru\EcmaScript\Harness\Contracts\FrontmatterNegative;
use Oru\EcmaScript\Harness\Contracts\TestRunner;
use Oru\EcmaScript\Harness\Test\Exception\AssertionFailedException;
use Oru\EcmaScript\Harness\Test\Exception\EngineException;
use Throwable;

use function Oru\EcmaScript\Operations\Abstract\get;
use function Oru\EcmaScript\Operations\Abstract\hasProperty;

abstract readonly class BaseTestRunner implements TestRunner
{
    /**
     * @throws AssertionFailedException
     * @throws EngineException
     */
    protected static function assertFailure(Agent $agent, mixed $completion, FrontmatterNegative $frontmatterNegative): void
    {
        $factory = $agent->getInterpreter()->getValueFactory();

        if (!$completion instanceof ThrowCompletion) {
            throw new AssertionFailedException('Expected `ThrowCompletion`');
        }

        $exception = $completion->getValue();

        if (!$exception instanceof ObjectValue) {
            throw new AssertionFailedException('`ThrowCompletion` does not contain an `ObjectValue`');
        }

        try {
            $constructor = get($agent, $exception, $factory->createString('constructor'));
        } catch (Throwable $throwable) {
            throw new EngineException('Could not use `get()` to retrieve `constructor`', previous: $throwable);
        }

        if (!$constructor instanceof ObjectValue) {
            throw new AssertionFailedException('Constructor value is not an `ObjectValue`');
        }

        try {
            $hasName = hasProperty($agent, $constructor, $factory->createString('name'));
        } catch (Throwable $throwable) {
            throw new EngineException('Could not use `hasName()` to to check existence of `name`', previous: $throwable);
        }

        if (!$hasName->getValue()) {
            throw new AssertionFailedException('Constructor does not have a name');
        }

        try {
            $name = (string) get($agent, $constructor, $factory->createString('name'));
        } catch (Throwable $throwable) {
            throw new EngineException('Could not use `get()` to retrieve `constructor.name`', previous: $throwable);
        }

        if ($frontmatterNegative->type() !== $name) {
            throw new AssertionFailedException("Expected `{$frontmatterNegative->type()}` but got `{$name}`");
        }
    }

    /**
     * @throws AssertionFailedException
     * @throws EngineException
     */
    protected static function assertSuccess(Agent $agent, mixed $completion): void
    {
        $factory = $agent->getInterpreter()->getValueFactory();

        if (!$completion instanceof AbruptCompletion) {
            return;
        }

        if (!$completion instanceof ThrowCompletion) {
            throw new AssertionFailedException('Expected `NormalCompletion`');
        }

        $value = $completion->getValue();

        if (!$value instanceof ObjectValue) {
            throw new AssertionFailedException((string) $value->getValue());
        }

        try {
            $message = $value->getOwnProperty($agent, $factory->createString('message'));
        } catch (Throwable $throwable) {
            throw new EngineException('Could not use `Object.[[GetOwnProperty]]()` to retrieve `message`', previous: $throwable);
        }

        if ($message instanceof UndefinedValue) {
            throw new AssertionFailedException("EngineError without message :(");
        }

        throw new AssertionFailedException($message->getValue($agent)->getValue());
    }
}
