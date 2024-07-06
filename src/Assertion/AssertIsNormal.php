<?php

/**
 * Copyright (c) 2023-2024, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Oru\Harness\Assertion;

use Oru\EcmaScript\Core\Contracts\Agent;
use Oru\EcmaScript\Core\Contracts\Values\AbruptCompletion;
use Oru\EcmaScript\Core\Contracts\Values\LanguageValue;
use Oru\EcmaScript\Core\Contracts\Values\ObjectValue;
use Oru\EcmaScript\Core\Contracts\Values\StringValue;
use Oru\EcmaScript\Core\Contracts\Values\ThrowCompletion;
use Oru\EcmaScript\Core\Contracts\Values\ValueFactory;
use Oru\Harness\Assertion\Exception\AssertionFailedException;
use Oru\Harness\Assertion\Exception\EngineException;
use Oru\Harness\Contracts\Assertion;

final readonly class AssertIsNormal implements Assertion
{
    private StringValue $messageString;

    public function __construct(
        private Agent $agent,
        ValueFactory $valueFactory,
    ) {
        $this->messageString = $valueFactory->createString('message');
    }

    /**
     * @throws AssertionFailedException
     * @throws EngineException
     */
    public function assert(mixed $actual): void
    {
        if ($actual instanceof LanguageValue) {
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
            $message = (string) $value->get($this->agent, $this->messageString, $value)->getValue();
        } catch (AbruptCompletion $throwable) {
            throw new EngineException('Could not convert object property `message` to string', previous: $throwable);
        }

        if ($message === '') {
            throw new EngineException('Object property `message` was empty');
        }

        throw new AssertionFailedException($message);
    }
}
