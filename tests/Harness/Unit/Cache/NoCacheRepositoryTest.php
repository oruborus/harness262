<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Cache;

use Oru\EcmaScript\Harness\Cache\NoCacheRepository;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestResultState;
use Oru\EcmaScript\Harness\Test\GenericTestResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NoCacheRepository::class)]
final class NoCacheRepositoryTest extends TestCase
{
    /**
     * @test
     */
    public function returnsNullOnGet(): void
    {
        $repository = new NoCacheRepository();
        $config = $this->createMock(TestConfig::class);

        $actual = $repository->get($config);

        $this->assertNull($actual);
    }

    /**
     * @test
     */
    public function returnsNullOnSet(): void
    {
        $repository = new NoCacheRepository();
        $config = $this->createMock(TestConfig::class);
        $result = new GenericTestResult(TestResultState::Success, [], 0);

        $actual = $repository->set($config, $result);

        $this->assertNull($actual);
    }
}
