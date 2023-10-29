<?php

declare(strict_types=1);

namespace Tests\Unit\Cache;

use Generator;
use Oru\Harness\Cache\GenericCacheRepository;
use Oru\Harness\Cache\GenericCacheRepositoryFactory;
use Oru\Harness\Cache\NoCacheRepository;
use Oru\Harness\Contracts\TestSuiteConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericCacheRepositoryFactory::class)]
final class GenericCacheRepositoryFactoryTest extends TestCase
{
    #[Test]
    #[DataProvider('provideCacheConfiguration')]
    public function createsTheCorrectCacheRepositoryBasedOnConfig(bool $cache, string $expected): void
    {
        $testSuiteConfigStub = $this->createConfiguredStub(TestSuiteConfig::class, [
            'cache' => $cache
        ]);
        $factory = new GenericCacheRepositoryFactory();

        $actual = $factory->make($testSuiteConfigStub);

        $this->assertInstanceOf($expected, $actual);
    }

    public static function provideCacheConfiguration(): Generator
    {
        yield 'disabled cache' => [false, NoCacheRepository::class];
        yield 'ensabled cache' => [true, GenericCacheRepository::class];
    }
}
