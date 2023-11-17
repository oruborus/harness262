<?php

declare(strict_types=1);

namespace Oru\Harness\Assertion;

use Oru\Harness\Contracts\Assertion;
use Oru\Harness\Assertion\Exception\AssertionFailedException;
use Oru\Harness\Assertion\Exception\EngineException;
use Oru\Harness\Contracts\Facade;
use Oru\Harness\Contracts\FrontmatterNegative;
use Throwable;

final readonly class AssertIsThrowableWithConstructor implements Assertion
{
    public function __construct(
        private Facade $facade,
        private FrontmatterNegative $negative
    ) {}

    /**
     * @throws AssertionFailedException
     * @throws EngineException
     *
     * @psalm-suppress MixedAssignment  The methods of `Facade` intentionally return `mixed`
     */
    public function assert(mixed $actual): void
    {
        if (!$this->facade->isThrowCompletion($actual)) {
            throw new AssertionFailedException('Expected `ThrowCompletion`');
        }

        $exception = $this->facade->completionGetValue($actual);

        if (!$this->facade->isObject($exception)) {
            throw new AssertionFailedException("`ThrowCompletion` does not contain an `ObjectValue`, got '{$this->facade->toString($exception)}'");
        }

        try {
            $constructor = $this->facade->objectGet($exception, 'constructor');
        } catch (Throwable $throwable) {
            throw new EngineException('Could not use `get()` to retrieve `constructor`', previous: $throwable);
        }

        if (!$this->facade->isObject($constructor)) {
            throw new AssertionFailedException('Constructor value is not an `ObjectValue`');
        }

        try {
            $hasName = $this->facade->objectHasProperty($constructor, 'name');
        } catch (Throwable $throwable) {
            throw new EngineException('Could not use `hasName()` to check existence of `name`', previous: $throwable);
        }

        if (!$hasName) {
            throw new AssertionFailedException('Constructor does not have a name');
        }

        try {
            $nameProperty = $this->facade->objectGet($constructor, 'name');
        } catch (Throwable $throwable) {
            throw new EngineException('Could not use `get()` to retrieve `constructor.name`', previous: $throwable);
        }

        try {
            $name = $this->facade->toString($nameProperty);
        } catch (Throwable $throwable) {
            throw new EngineException('Could not convert `name` to string', previous: $throwable);
        }

        if ($this->negative->type() !== $name) {
            throw new AssertionFailedException("Expected `{$this->negative->type()}` but got `{$name}`");
        }
    }
}
