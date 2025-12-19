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

namespace Tests\Unit\Cache;

use Oru\Harness\Cache\GenericCacheResultRecord;
use Oru\Harness\Contracts\TestResult;
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
        $expectedResult = $this->createStub(TestResult::class);

        $cacheResultRecord = new GenericCacheResultRecord($expectedHash, $expectedUsedFiles, $expectedResult);

        $this->assertSame($expectedHash, $cacheResultRecord->hash());
        $this->assertSame($expectedUsedFiles, $cacheResultRecord->usedFiles());
        $this->assertSame($expectedResult, $cacheResultRecord->result());
    }
}
