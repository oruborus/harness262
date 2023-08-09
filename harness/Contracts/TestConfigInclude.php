<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Contracts;

use RuntimeException;

enum TestConfigInclude: string
{
    case assert                    = './vendor/tc39/test262/harness/assert.js';
    case assertRelativeDateMs      = './vendor/tc39/test262/harness/assertRelativeDateMs.js';
    case asyncGc                   = './vendor/tc39/test262/harness/async-gc.js';
    case asyncHelpers              = './vendor/tc39/test262/harness/asyncHelpers.js';
    case atomicsHelper             = './vendor/tc39/test262/harness/atomicsHelper.js';
    case byteConversionValues      = './vendor/tc39/test262/harness/byteConversionValues.js';
    case compareArray              = './vendor/tc39/test262/harness/compareArray.js';
    case compareIterator           = './vendor/tc39/test262/harness/compareIterator.js';
    case dateConstants             = './vendor/tc39/test262/harness/dateConstants.js';
    case decimalToHexString        = './vendor/tc39/test262/harness/decimalToHexString.js';
    case deepEqual                 = './vendor/tc39/test262/harness/deepEqual.js';
    case detachArrayBuffer         = './vendor/tc39/test262/harness/detachArrayBuffer.js';
    case doneprintHandle           = './vendor/tc39/test262/harness/doneprintHandle.js';
    case fnGlobalObject            = './vendor/tc39/test262/harness/fnGlobalObject.js';
    case hiddenConstructors        = './vendor/tc39/test262/harness/hidden-constructors.js';
    case isConstructor             = './vendor/tc39/test262/harness/isConstructor.js';
    case nans                      = './vendor/tc39/test262/harness/nans.js';
    case nativeFunctionMatcher     = './vendor/tc39/test262/harness/nativeFunctionMatcher.js';
    case promiseHelper             = './vendor/tc39/test262/harness/promiseHelper.js';
    case propertyHelper            = './vendor/tc39/test262/harness/propertyHelper.js';
    case proxyTrapsHelper          = './vendor/tc39/test262/harness/proxyTrapsHelper.js';
    case regExpUtils               = './vendor/tc39/test262/harness/regExpUtils.js';
    case sta                       = './vendor/tc39/test262/harness/sta.js';
    case tcoHelper                 = './vendor/tc39/test262/harness/tcoHelper.js';
    case temporalHelpers           = './vendor/tc39/test262/harness/temporalHelpers.js';
    case testAtomics               = './vendor/tc39/test262/harness/testAtomics.js';
    case testBigIntTypedArray      = './vendor/tc39/test262/harness/testBigIntTypedArray.js';
    case testIntl                  = './vendor/tc39/test262/harness/testIntl.js';
    case testTypedArray            = './vendor/tc39/test262/harness/testTypedArray.js';
    case timer                     = './vendor/tc39/test262/harness/timer.js';
    case typeCoercion              = './vendor/tc39/test262/harness/typeCoercion.js';
    case wellKnownIntrinsicObjects = './vendor/tc39/test262/harness/wellKnownIntrinsicObjects.js';

    public static function fromString(string $include): static
    {
        return match ($include) {
            'assert.js'                    => static::assert,
            'assertRelativeDateMs.js'      => static::assertRelativeDateMs,
            'async-gc.js'                  => static::asyncGc,
            'asyncHelpers.js'              => static::asyncHelpers,
            'atomicsHelper.js'             => static::atomicsHelper,
            'byteConversionValues.js'      => static::byteConversionValues,
            'compareArray.js'              => static::compareArray,
            'compareIterator.js'           => static::compareIterator,
            'dateConstants.js'             => static::dateConstants,
            'decimalToHexString.js'        => static::decimalToHexString,
            'deepEqual.js'                 => static::deepEqual,
            'detachArrayBuffer.js'         => static::detachArrayBuffer,
            'doneprintHandle.js'           => static::doneprintHandle,
            'fnGlobalObject.js'            => static::fnGlobalObject,
            'hidden-constructors.js'       => static::hiddenConstructors,
            'isConstructor.js'             => static::isConstructor,
            'nans.js'                      => static::nans,
            'nativeFunctionMatcher.js'     => static::nativeFunctionMatcher,
            'promiseHelper.js'             => static::promiseHelper,
            'propertyHelper.js'            => static::propertyHelper,
            'proxyTrapsHelper.js'          => static::proxyTrapsHelper,
            'regExpUtils.js'               => static::regExpUtils,
            'sta.js'                       => static::sta,
            'tcoHelper.js'                 => static::tcoHelper,
            'temporalHelpers.js'           => static::temporalHelpers,
            'testAtomics.js'               => static::testAtomics,
            'testBigIntTypedArray.js'      => static::testBigIntTypedArray,
            'testIntl.js'                  => static::testIntl,
            'testTypedArray.js'            => static::testTypedArray,
            'timer.js'                     => static::timer,
            'typeCoercion.js'              => static::typeCoercion,
            'wellKnownIntrinsicObjects.js' => static::wellKnownIntrinsicObjects,
            default => throw new RuntimeException("Unknown include `{$include}`")
        };
    }
}
