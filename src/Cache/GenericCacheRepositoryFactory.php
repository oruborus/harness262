<?php

declare(strict_types=1);

namespace Oru\Harness\Cache;

use Oru\Harness\Contracts\CacheRepository;
use Oru\Harness\Contracts\CacheRepositoryFactory;
use Oru\Harness\Contracts\CacheResultRecord;
use Oru\Harness\Contracts\Storage;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestSuiteConfig;
use Oru\Harness\Storage\SerializingFileStorage;

final class GenericCacheRepositoryFactory implements CacheRepositoryFactory
{
    public function make(TestSuiteConfig $config): CacheRepository
    {
        /** 
         * @var Storage<CacheResultRecord> $storage
         */
        $storage = new SerializingFileStorage('./.harness/cache');

        return $config->cache() ?
            new GenericCacheRepository(
                $storage,
                static fn (TestConfig $i): string => md5(serialize($i)),
                static fn (string $i): string => hash_file('haval160,4', $i)
            ) :
            new NoCacheRepository();
    }
}
