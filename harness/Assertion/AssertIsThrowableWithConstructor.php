<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Assertion;

use Oru\EcmaScript\Core\Contracts\Agent;
use Oru\EcmaScript\Core\Contracts\Values\ObjectValue;
use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
use Oru\EcmaScript\Harness\Contracts\Assertion;
use Oru\EcmaScript\Harness\Assertion\Exception\AssertionFailedException;
use Oru\EcmaScript\Harness\Assertion\Exception\EngineException;
use Oru\EcmaScript\Harness\Contracts\FrontmatterNegative;
use Throwable;

use function Oru\EcmaScript\Operations\Abstract\get;
use function Oru\EcmaScript\Operations\Abstract\hasProperty;

final readonly class AssertIsThrowableWithConstructor implements Assertion
{
    public function __construct(
        private Agent $agent,
        private FrontmatterNegative $negative
    ) {
    }

    /**
     * @throws AssertionFailedException
     * @throws EngineException
     */
    public function assert(mixed $actual): void
    {
        $factory = $this->agent->getInterpreter()->getValueFactory();

        if (!$actual instanceof ThrowCompletion) {
            throw new AssertionFailedException('Expected `ThrowCompletion`');
        }

        $exception = $actual->getValue();

        if (!$exception instanceof ObjectValue) {
            throw new AssertionFailedException('`ThrowCompletion` does not contain an `ObjectValue`');
        }

        try {
            $constructor = get($this->agent, $exception, $factory->createString('constructor'));
        } catch (Throwable $throwable) {
            throw new EngineException('Could not use `get()` to retrieve `constructor`', previous: $throwable);
        }

        if (!$constructor instanceof ObjectValue) {
            throw new AssertionFailedException('Constructor value is not an `ObjectValue`');
        }

        try {
            $hasName = hasProperty($this->agent, $constructor, $factory->createString('name'));
        } catch (Throwable $throwable) {
            throw new EngineException('Could not use `hasName()` to to check existence of `name`', previous: $throwable);
        }

        if (!$hasName->getValue()) {
            throw new AssertionFailedException('Constructor does not have a name');
        }

        try {
            $name = (string) get($this->agent, $constructor, $factory->createString('name'));
        } catch (Throwable $throwable) {
            throw new EngineException('Could not use `get()` to retrieve `constructor.name`', previous: $throwable);
        }

        if ($this->negative->type() !== $name) {
            throw new AssertionFailedException("Expected `{$this->negative->type()}` but got `{$name}`");
        }
    }
}
