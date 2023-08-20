<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Assertion;

use Oru\EcmaScript\Core\Contracts\Agent;
use Oru\EcmaScript\Core\Contracts\Values\AbruptCompletion;
use Oru\EcmaScript\Core\Contracts\Values\ObjectValue;
use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
use Oru\EcmaScript\Core\Contracts\Values\UndefinedValue;
use Oru\EcmaScript\Harness\Contracts\Assertion;
use Oru\EcmaScript\Harness\Assertion\Exception\AssertionFailedException;
use Oru\EcmaScript\Harness\Assertion\Exception\EngineException;
use Throwable;

final readonly class AssertIsNotThrowable implements Assertion
{
    public function __construct(
        private Agent $agent
    ) {
    }

    /**
     * @throws AssertionFailedException
     * @throws EngineException
     */
    public function assert(mixed $actual): void
    {
        $factory = $this->agent->getInterpreter()->getValueFactory();

        if (!$actual instanceof AbruptCompletion) {
            return;
        }

        if (!$actual instanceof ThrowCompletion) {
            throw new AssertionFailedException('Expected `NormalCompletion`');
        }

        $value = $actual->getValue();

        if (!$value instanceof ObjectValue) {
            throw new AssertionFailedException((string) $value->getValue());
        }

        try {
            $message = $value->getOwnProperty($this->agent, $factory->createString('message'));
        } catch (Throwable $throwable) {
            throw new EngineException('Could not use `Object.[[GetOwnProperty]]()` to retrieve `message`', previous: $throwable);
        }

        if ($message instanceof UndefinedValue) {
            throw new AssertionFailedException("EngineError without message :(");
        }

        throw new AssertionFailedException($message->getValue($this->agent)->getValue());
    }
}
