<?php

declare(strict_types=1);

use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\EcmaScript\Core\Contracts\Values\AbruptCompletion;
use Oru\EcmaScript\Core\Contracts\Values\CompletionValue;
use Oru\EcmaScript\Core\Contracts\Values\LanguageValue;
use Oru\EcmaScript\Core\Contracts\Values\ObjectValue;
use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
use Oru\EcmaScript\Core\Contracts\Values\UndefinedValue;
use Oru\EcmaScript\EngineImplementation;
use Oru\EcmaScript\Harness\Contracts\Facade;
use Tests\Test262\Utilities\PrintIntrinsic;
use Tests\Test262\Utilities\S262Intrinsic;

use function Oru\EcmaScript\Operations\Abstract\get;
use function Oru\EcmaScript\Operations\TypeConversions\toString;

return static function (): Facade {
    $engine = new EngineImplementation(
        hostDefinedProperties: [
            '$262' => S262Intrinsic::class,
            'print' => PrintIntrinsic::class
        ]
    );

    return new class($engine) implements Facade
    {
        public function __construct(
            private Engine $engine
        ) {
        }

        public function completionGetValue(mixed $completion): mixed
        {
            assert($completion instanceof CompletionValue);

            return $completion->getValue();
        }

        /**
         * @psalm-assert-if-false AbruptCompletion $value
         */
        public function isNormalCompletion(mixed $value): bool
        {
            return !$value instanceof AbruptCompletion;
        }

        /**
         * @psalm-assert-if-true ThrowCompletion $value
         */
        public function isThrowCompletion(mixed $value): bool
        {
            return $value instanceof ThrowCompletion;
        }

        /**
         * @psalm-assert-if-true ObjectValue $value
         */
        public function isObject(mixed $value): bool
        {
            return $value instanceof ObjectValue;
        }

        /**
         * @psalm-assert-if-true UndefinedValue $value
         */
        public function isUndefined(mixed $value): bool
        {
            return $value instanceof UndefinedValue;
        }

        /**
         * @throws AbruptCompletion
         *
         * @psalm-suppress MixedAssignment  The methods of `Facade` intentionally return `mixed`
         * @psalm-suppress MixedArgument    The methods of `Facade` intentionally take `mixed` as parameter types
         */
        public function objectGetAsString(mixed $object, string $propertyKey): string
        {
            $property = $this->objectGet($object, $propertyKey);

            return (string) toString($this->engine->getAgent(), $property);
        }

        /**
         * @throws AbruptCompletion
         */
        public function objectGet(mixed $object, string $propertyKey): mixed
        {
            assert($object instanceof ObjectValue);

            $propertyKey = $this->engine->getAgent()->getInterpreter()->getValueFactory()->createString($propertyKey);

            return get($this->engine->getAgent(), $object, $propertyKey);
        }

        /**
         * @throws AbruptCompletion
         */
        public function objectHasProperty(mixed $object, string $propertyKey): bool
        {
            assert($object instanceof ObjectValue);

            return $object->hasProperty($this->engine->getAgent(), $this->engine->getAgent()->getInterpreter()->getValueFactory()->createString($propertyKey))->getValue();
        }

        public function toString(mixed $value): string
        {
            assert($value instanceof LanguageValue);

            return (string) toString($this->engine->getAgent(), $value);
        }

        /**
         * @return array<int, string>
         */
        public function engineSupportedFeatures(): array
        {
            return $this->engine->getSupportedFeatures();
        }

        public function engineAddFiles(string ...$paths): void
        {
            $this->engine->addFiles(...$paths);
        }

        public function engineAddCode(string $source, ?string $file = null, bool $isModuleCode = false): void
        {
            $this->engine->addCode($source, $file, $isModuleCode);
        }

        public function engineRun(): mixed
        {
            return $this->engine->run();
        }
    };
};
