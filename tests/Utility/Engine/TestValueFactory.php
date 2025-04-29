<?php

/**
 * Copyright (c) 2023-2025, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Tests\Utility\Engine;

use Oru\EcmaScript\Core\Contracts\Grammars\ScriptsAndModules\Productions\Module as ProductionsModule;
use Oru\EcmaScript\Core\Contracts\Values\BooleanValue;
use Oru\EcmaScript\Core\Contracts\Values\AlreadyResolvedRecord;
use Oru\EcmaScript\Core\Contracts\Values\LanguageValue;
use Oru\EcmaScript\Core\Contracts\Values\AbruptCompletion;
use Oru\EcmaScript\Core\Contracts\Values\PromiseCapabilityRecord;
use Oru\EcmaScript\Core\Contracts\Values\AsyncGeneratorRequest;
use Oru\EcmaScript\Core\Contracts\Values\BigIntValue;
use Oru\EcmaScript\Core\Contracts\Values\EmptyValue;
use Oru\EcmaScript\Core\Contracts\Values\StringValue;
use Oru\EcmaScript\Core\Contracts\Values\BreakCompletion;
use Oru\EcmaScript\Core\Contracts\Values\CharSet;
use Oru\EcmaScript\Core\Contracts\Values\CodePointRecord;
use Oru\EcmaScript\Core\Contracts\Values\ContinueCompletion;
use Oru\EcmaScript\Core\Contracts\Values\NullValue;
use Oru\EcmaScript\Core\Contracts\Values\EnvironmentRecord;
use Oru\EcmaScript\Core\Contracts\Values\DeclarativeEnvironmentRecord;
use Oru\EcmaScript\Core\Contracts\Values\DataBlock;
use Oru\EcmaScript\Core\Contracts\Values\ObjectValue;
use Oru\EcmaScript\Core\Contracts\Values\RealmRecord;
use Oru\EcmaScript\Core\Contracts\Values\ScriptRecord;
use Oru\EcmaScript\Core\Contracts\Values\ModuleRecord;
use Oru\EcmaScript\Core\Contracts\Values\UndefinedValue;
use Oru\EcmaScript\Core\Contracts\Values\ExecutionContext;
use Oru\EcmaScript\Core\Contracts\Values\ImportName;
use Oru\EcmaScript\Core\Contracts\Values\ExportEntry;
use Oru\EcmaScript\Core\Contracts\Values\FunctionEnvironmentRecord;
use Oru\EcmaScript\Core\Contracts\Values\ObjectEnvironmentRecord;
use Oru\EcmaScript\Core\Contracts\Values\ListValue;
use Oru\EcmaScript\Core\Contracts\Values\GlobalEnvironmentRecord;
use Oru\EcmaScript\Core\Contracts\Values\GoalSymbol;
use Oru\EcmaScript\Core\Contracts\Values\NumberValue;
use Oru\EcmaScript\Core\Contracts\Values\GraphLoadingState;
use Oru\EcmaScript\Core\Contracts\Values\IteratorRecord;
use Oru\EcmaScript\Core\Contracts\Values\JobCallback;
use Oru\EcmaScript\Core\Contracts\Values\MathematicalValue;
use Oru\EcmaScript\Core\Contracts\Values\SymbolValue;
use Oru\EcmaScript\Core\Contracts\Values\MethodDefinitionRecord;
use Oru\EcmaScript\Core\Contracts\Values\ModuleEnvironmentRecord;
use Oru\EcmaScript\Core\Contracts\Values\ModuleExportNamePair;
use Oru\EcmaScript\Core\Contracts\Values\NormalCompletion;
use Oru\EcmaScript\Core\Contracts\Parameters;
use Oru\EcmaScript\Core\Contracts\Values\PatternEvaluationResult;
use Oru\EcmaScript\Core\Contracts\Values\PromiseReactionType;
use Oru\EcmaScript\Core\Contracts\Values\PromiseReaction;
use Oru\EcmaScript\Core\Contracts\Values\PromiseReactionJob;
use Oru\EcmaScript\Core\Contracts\Values\PropertyDescriptor;
use Oru\EcmaScript\Core\Contracts\Values\QuantifierEvaluationResult;
use Oru\EcmaScript\Core\Contracts\Values\QuantifierPrefixEvaluationResult;
use Oru\EcmaScript\Core\Contracts\Values\ResolvedBinding;
use Oru\EcmaScript\Core\Contracts\Values\ResolveRejectRecord;
use Oru\EcmaScript\Core\Contracts\Values\ReturnCompletion;
use Oru\EcmaScript\Core\Contracts\Values\Status;
use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
use Oru\EcmaScript\Core\Contracts\Values\CyclicModuleRecord;
use Oru\EcmaScript\Core\Contracts\Nodes\Module;
use Oru\EcmaScript\Core\Contracts\ParameterName;
use Oru\EcmaScript\Core\Contracts\Values\CaptureRange;
use Oru\EcmaScript\Core\Contracts\Values\Failure;
use Oru\EcmaScript\Core\Contracts\Values\MatchRecord;
use Oru\EcmaScript\Core\Contracts\Values\MatchState;
use Oru\EcmaScript\Core\Contracts\Values\SourceTextModuleRecord;
use Oru\EcmaScript\Core\Contracts\Values\SpecifierModulePair;
use Oru\EcmaScript\Core\Contracts\Values\State;
use Oru\EcmaScript\Core\Contracts\Values\TypedArrayType;
use Oru\EcmaScript\Core\Contracts\Values\UnusedValue;
use Oru\EcmaScript\Core\Contracts\Values\ReferenceRecord;
use Oru\EcmaScript\Core\Contracts\Values\RegExpRecord;
use Oru\EcmaScript\Core\Contracts\Values\SourceText;
use Oru\EcmaScript\Core\Contracts\Values\ValueFactory;
use Stringable;

final class TestValueFactory implements ValueFactory
{
    public function createCaptureRange(NumberValue $startIndex, NumberValue $endIndex): CaptureRange
    {
        throw new \RuntimeException('`TestValueFactory::createCaptureRange()` is not implemented');
    }

    public function createFailure(): Failure
    {
        throw new \RuntimeException('`TestValueFactory::createFailure()` is not implemented');
    }

    public function createMatchRecord(NumberValue $startIndex, NumberValue $endIndex): MatchRecord
    {
        throw new \RuntimeException('`TestValueFactory::createMatchRecord()` is not implemented');
    }

    public function createMatchState(ListValue $input, NumberValue $endIndex, array $captures): MatchState
    {
        throw new \RuntimeException('`TestValueFactory::createMatchState()` is not implemented');
    }

    public function createRegExpRecord(BooleanValue $ignoreCase, BooleanValue $multiline, BooleanValue $dotAll, BooleanValue $unicode, BooleanValue $unicodeSets, NumberValue $capturingGroupsCount): RegExpRecord
    {
        throw new \RuntimeException('`TestValueFactory::createRegExpRecord()` is not implemented');
    }

    public function createSourceText(array $values): SourceText
    {
        throw new \RuntimeException('`TestValueFactory::createSourceText()` is not implemented');
    }

    public function createAlreadyResolvedRecord(BooleanValue $value): AlreadyResolvedRecord
    {
        throw new \RuntimeException('`TestValueFactory::createAlreadyResolvedRecord()` is not implemented');
    }

    public function createAsyncGeneratorRequest(null|LanguageValue|AbruptCompletion $completion, PromiseCapabilityRecord $capability): AsyncGeneratorRequest
    {
        throw new \RuntimeException('`TestValueFactory::createAsyncGeneratorRequest()` is not implemented');
    }

    public function createBigInt(string|Stringable $value): BigIntValue
    {
        throw new \RuntimeException('`TestValueFactory::createBigInt()` is not implemented');
    }

    public function createBoolean(bool $value): BooleanValue
    {
        throw new \RuntimeException('`TestValueFactory::createBoolean()` is not implemented');
    }

    public function createBreakCompletion(LanguageValue $value, null|EmptyValue|StringValue $target = null): BreakCompletion
    {
        throw new \RuntimeException('`TestValueFactory::createBreakCompletion()` is not implemented');
    }

    public function createCharSet(array|callable $characters): CharSet
    {
        throw new \RuntimeException('`TestValueFactory::createCharSet()` is not implemented');
    }

    public function createCodePointRecord(int $codePoint, int $codeUnitCount, bool $isUnpairedSurrogate): CodePointRecord
    {
        throw new \RuntimeException('`TestValueFactory::createCodePointRecord()` is not implemented');
    }

    public function createContinueCompletion(LanguageValue $value, null|EmptyValue|StringValue $target = null): ContinueCompletion
    {
        throw new \RuntimeException('`TestValueFactory::createContinueCompletion()` is not implemented');
    }

    public function createDeclarativeEnvironmentRecord(null|NullValue|EnvironmentRecord $outerEnv = null): DeclarativeEnvironmentRecord
    {
        throw new \RuntimeException('`TestValueFactory::createDeclarativeEnvironmentRecord()` is not implemented');
    }

    public function createDataBlock(int $size): DataBlock
    {
        throw new \RuntimeException('`TestValueFactory::createDataBlock()` is not implemented');
    }

    public function createEmpty(): EmptyValue
    {
        throw new \RuntimeException('`TestValueFactory::createEmpty()` is not implemented');
    }

    public function createEnvironment(): EnvironmentRecord
    {
        throw new \RuntimeException('`TestValueFactory::createEnvironment()` is not implemented');
    }

    public function createExecutionContext(ObjectValue|NullValue $function, RealmRecord $realm, ScriptRecord|ModuleRecord|NullValue $scriptOrModule, null|NullValue|EnvironmentRecord $lexicalEnvironment = null, null|NullValue|EnvironmentRecord $variableEnvironment = null, null|UndefinedValue|ObjectValue $generator = null): ExecutionContext
    {
        throw new \RuntimeException('`TestValueFactory::createExecutionContext()` is not implemented');
    }

    public function createExportEntry(StringValue|NullValue $exportName, StringValue|NullValue $moduleRequest, StringValue|NullValue|ImportName $importName, StringValue|NullValue $localName): ExportEntry
    {
        throw new \RuntimeException('`TestValueFactory::createExportEntry()` is not implemented');
    }

    public function createFunctionEnvironmentRecord(ObjectValue $functionObject, ObjectValue|UndefinedValue $newTarget, NullValue|EnvironmentRecord $outerEnv, string $thisBindingStatus): FunctionEnvironmentRecord
    {
        throw new \RuntimeException('`TestValueFactory::createFunctionEnvironmentRecord()` is not implemented');
    }

    public function createGlobalEnvironmentRecord(ObjectEnvironmentRecord $objectRecord, ObjectValue $globalThisValue, DeclarativeEnvironmentRecord $declarativeRecord, NullValue|EnvironmentRecord $outerEnv, ListValue $varNames): GlobalEnvironmentRecord
    {
        throw new \RuntimeException('`TestValueFactory::createGlobalEnvironmentRecord()` is not implemented');
    }

    public function createGoalSymbol(string $node, ParameterName ...$parameterNames): GoalSymbol
    {
        throw new \RuntimeException('`TestValueFactory::createGoalSymbol()` is not implemented');
    }

    public function createGraphLoadingState(PromiseCapabilityRecord $promiseCapability, BooleanValue $isLoading, NumberValue $pendingModulesCount, ListValue $visited, mixed $hostDefined = null): GraphLoadingState
    {
        throw new \RuntimeException('`TestValueFactory::createGraphLoadingState()` is not implemented');
    }

    public function createIteratorRecord(ObjectValue $iterator, ObjectValue $nextMethod, BooleanValue $done): IteratorRecord
    {
        throw new \RuntimeException('`TestValueFactory::createIteratorRecord()` is not implemented');
    }

    public function createJobCallback(ObjectValue $callback, mixed $hostDefined): JobCallback
    {
        throw new \RuntimeException('`TestValueFactory::createJobCallback()` is not implemented');
    }

    public function createList(array $value = []): ListValue
    {
        throw new \RuntimeException('`TestValueFactory::createList()` is not implemented');
    }

    public function createMathematicalValue(Stringable|string $integerPart = '0', Stringable|string $decimalPart = '0', Stringable|string $exponentPart = '0'): MathematicalValue
    {
        throw new \RuntimeException('`TestValueFactory::createMathematicalValue()` is not implemented');
    }

    public function createMethodDefinitionRecord(StringValue|SymbolValue $key, ObjectValue $closure): MethodDefinitionRecord
    {
        throw new \RuntimeException('`TestValueFactory::createMethodDefinitionRecord()` is not implemented');
    }

    public function createModuleEnvironmentRecord(): ModuleEnvironmentRecord
    {
        throw new \RuntimeException('`TestValueFactory::createModuleEnvironmentRecord()` is not implemented');
    }

    public function createModuleExportNamePair(ModuleRecord $module, StringValue $specifier): ModuleExportNamePair
    {
        throw new \RuntimeException('`TestValueFactory::createModuleExportNamePair()` is not implemented');
    }

    public function createNormalCompletion(LanguageValue $value, ?StringValue $target = null): NormalCompletion
    {
        throw new \RuntimeException('`TestValueFactory::createNormalCompletion()` is not implemented');
    }

    public function createNull(): NullValue
    {
        throw new \RuntimeException('`TestValueFactory::createNull()` is not implemented');
    }

    public function createNumber(int|float $value): NumberValue
    {
        throw new \RuntimeException('`TestValueFactory::createNumber()` is not implemented');
    }

    public function createObject(ListValue $internalSlotsList): ObjectValue
    {
        throw new \RuntimeException('`TestValueFactory::createObject()` is not implemented');
    }

    public function createObjectEnvironmentRecord(ObjectValue $bindingObject, BooleanValue $isWithEnvironment, NullValue|EnvironmentRecord $outerEnv): ObjectEnvironmentRecord
    {
        throw new \RuntimeException('`TestValueFactory::createObjectEnvironmentRecord()` is not implemented');
    }

    public function createParameters(ParameterName ...$parameterNames): Parameters
    {
        throw new \RuntimeException('`TestValueFactory::createParameters()` is not implemented');
    }

    public function createPatternEvaluationResult(CharSet $charSet, BooleanValue $invert): PatternEvaluationResult
    {
        throw new \RuntimeException('`TestValueFactory::createPatternEvaluationResult()` is not implemented');
    }

    public function createPromiseCapabilityRecord(ObjectValue $promise, ObjectValue $resolve, ObjectValue $reject): PromiseCapabilityRecord
    {
        throw new \RuntimeException('`TestValueFactory::createPromiseCapabilityRecord()` is not implemented');
    }

    public function createPromiseReaction(UndefinedValue|PromiseCapabilityRecord $capability, PromiseReactionType $type, EmptyValue|JobCallback $handler): PromiseReaction
    {
        throw new \RuntimeException('`TestValueFactory::createPromiseReaction()` is not implemented');
    }

    public function createPromiseReactionJob(callable $job, NullValue|RealmRecord $realm): PromiseReactionJob
    {
        throw new \RuntimeException('`TestValueFactory::createPromiseReactionJob()` is not implemented');
    }

    public function createPropertyDescriptor(?LanguageValue $value = null, null|ObjectValue|UndefinedValue $get = null, null|ObjectValue|UndefinedValue $set = null, ?BooleanValue $writable = null, ?BooleanValue $enumerable = null, ?BooleanValue $configurable = null): PropertyDescriptor
    {
        throw new \RuntimeException('`TestValueFactory::createPropertyDescriptor()` is not implemented');
    }

    public function createQuantifierEvaluationResult(int $min, int|float $max, bool $greedy): QuantifierEvaluationResult
    {
        throw new \RuntimeException('`TestValueFactory::createQuantifierEvaluationResult()` is not implemented');
    }

    public function createQuantifierPrefixEvaluationResult(int $min, int|float $max): QuantifierPrefixEvaluationResult
    {
        throw new \RuntimeException('`TestValueFactory::createQuantifierPrefixEvaluationResult()` is not implemented');
    }

    public function createRealm(): RealmRecord
    {
        throw new \RuntimeException('`TestValueFactory::createRealm()` is not implemented');
    }

    public function createResolvedBinding(ModuleRecord $module, StringValue|ImportName $bindingName): ResolvedBinding
    {
        throw new \RuntimeException('`TestValueFactory::createResolvedBinding()` is not implemented');
    }

    public function createResolveRejectRecord(UndefinedValue|ObjectValue $resolve, UndefinedValue|ObjectValue $reject): ResolveRejectRecord
    {
        throw new \RuntimeException('`TestValueFactory::createResolveRejectRecord()` is not implemented');
    }

    public function createReturnCompletion(LanguageValue $value, ?StringValue $target = null): ReturnCompletion
    {
        throw new \RuntimeException('`TestValueFactory::createReturnCompletion()` is not implemented');
    }

    public function createScriptRecord(): ScriptRecord
    {
        throw new \RuntimeException('`TestValueFactory::createScriptRecord()` is not implemented');
    }

    public function createSourceTextModuleRecord(
        RealmRecord $realm,
        EnvironmentRecord|EmptyValue $environment,
        ObjectValue|EmptyValue $namespace,
        mixed $hostDefined,
        Status $status,
        ThrowCompletion|EmptyValue $evaluationError,
        NumberValue|EmptyValue $dfsIndex,
        NumberValue|EmptyValue $dfsAncestorIndex,
        ListValue $requestedModules,
        ListValue $loadedModules,
        CyclicModuleRecord|EmptyValue $cycleRoot,
        BooleanValue $hasTLA,
        BooleanValue $asyncEvaluation,
        PromiseCapabilityRecord|EmptyValue $topLevelCapability,
        ListValue $asyncParentModules,
        NumberValue|EmptyValue $pendingAsyncDependencies,
        ProductionsModule $ecmaScriptCode,
        ExecutionContext|EmptyValue $context,
        ObjectValue|EmptyValue $importMeta,
        ListValue $importEntries,
        ListValue $localExportEntries,
        ListValue $indirectExportEntries,
        ListValue $starExportEntries
    ): SourceTextModuleRecord {
        throw new \RuntimeException('`TestValueFactory::createSourceTextModuleRecord()` is not implemented');
    }

    public function createSpecifierModulePair(StringValue $specifier, ModuleRecord $module): SpecifierModulePair
    {
        throw new \RuntimeException('`TestValueFactory::createSpecifierModulePair()` is not implemented');
    }

    public function createState(int $endIndex, array $captures): State
    {
        throw new \RuntimeException('`TestValueFactory::createState()` is not implemented');
    }

    public function createString(string|array|Stringable $value): StringValue
    {
        return new TestStringValue($value);
    }

    public function createSymbol(null|string|StringValue|UndefinedValue $description = null): SymbolValue
    {
        throw new \RuntimeException('`TestValueFactory::createSymbol()` is not implemented');
    }

    public function createThrowCompletion(LanguageValue $value, ?StringValue $target = null): ThrowCompletion
    {
        throw new \RuntimeException('`TestValueFactory::createThrowCompletion()` is not implemented');
    }

    public function createTypedArrayType(string $case): TypedArrayType
    {
        throw new \RuntimeException('`TestValueFactory::createTypedArrayType()` is not implemented');
    }

    public function createUndefined(): UndefinedValue
    {
        throw new \RuntimeException('`TestValueFactory::createUndefined()` is not implemented');
    }

    public function createUnused(): UnusedValue
    {
        throw new \RuntimeException('`TestValueFactory::createUnused()` is not implemented');
    }

    public function createReferenceRecord(null|LanguageValue|EnvironmentRecord $base, StringValue|SymbolValue $referencedName, BooleanValue $strict, ?LanguageValue $thisValue = null): ReferenceRecord
    {
        throw new \RuntimeException('`TestValueFactory::createReferenceRecord()` is not implemented');
    }
}
