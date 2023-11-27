<?php

/**
 * Copyright (c) 2023, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Oru\Harness\Cache;

use Oru\Harness\Contracts\CacheRepository;
use Oru\Harness\Contracts\CacheRepositoryFactory;
use Oru\Harness\Contracts\CacheResultRecord;
use Oru\Harness\Contracts\Storage;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestSuite;
use Oru\Harness\Storage\SerializingFileStorage;

final class GenericCacheRepositoryFactory implements CacheRepositoryFactory
{
    public function make(TestSuite $testSuite): CacheRepository
    {
        /**
         * @var Storage<CacheResultRecord> $storage
         */
        $storage = new SerializingFileStorage('./.harness/cache');

        return $testSuite->cache() ?
            new GenericCacheRepository(
                $storage,
                static fn(TestCase $i): string => md5(serialize($i)),
                static fn(string $i): string => hash_file('haval160,4', $i)
            ) :
            new NoCacheRepository();
    }
}
