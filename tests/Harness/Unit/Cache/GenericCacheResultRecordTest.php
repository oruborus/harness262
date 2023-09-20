<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Cache;

use Oru\EcmaScript\Harness\Cache\GenericCacheResultRecord;
use Oru\EcmaScript\Harness\Contracts\TestResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericCacheResultRecord::class)]
final class GenericCacheResultRecordTest extends TestCase
{
    #[Test]
    public function actsAsValueObject(): void
    {
        $expectedHash = 'SOME_HASH';
        $expectedUsedFiles = ['usedFile1', 'usedFile2', 'usedFile3'];
        $expectedResult = $this->createMock(TestResult::class);

        $cacheResultRecord = new GenericCacheResultRecord($expectedHash, $expectedUsedFiles, $expectedResult);

        $this->assertSame($expectedHash, $cacheResultRecord->hash());
        $this->assertSame($expectedUsedFiles, $cacheResultRecord->usedFiles());
        $this->assertSame($expectedResult, $cacheResultRecord->result());
    }
}
