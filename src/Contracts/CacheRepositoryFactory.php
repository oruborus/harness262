<?php

declare(strict_types=1);

namespace Oru\Harness\Contracts;

interface CacheRepositoryFactory
{
    public function make(TestSuiteConfig $config): CacheRepository;
}
