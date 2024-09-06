<?php

/**
 * Copyright (c) 2024, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Oru\Harness\Helpers;

use Exception;

use function serialize;
use function unserialize;

final readonly class Serializer
{
    /** @throws Exception */
    public function serialize(mixed $value): string
    {
        return serialize((new SerializationSanitizer())->sanitize($value));
    }

    public function unserialize(string $value): mixed
    {
        return unserialize($value);
    }
}
