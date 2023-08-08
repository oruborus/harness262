<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Test;

use Oru\EcmaScript\Harness\Test\GenericTestConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericTestConfig::class)]
final class GenericTestConfigTest extends TestCase
{
    /**
     * @test
     */
    public function actsAsValueObject(): void
    {
        $expectedPath = 'path/to/file';
        $expectedContent = 'CONTENT';
        $expectedFlags = ['flag1', 'flag2'];
        $expectedIncludes = ['include1.js', 'include2.js'];
        $expectedFeatures = ['feature1', 'feature2'];
        $expectedNegative = ['phase' => 'parse', 'type' => 'SyntaxError'];

        $actual = new GenericTestConfig($expectedPath, $expectedContent, $expectedFlags, $expectedIncludes, $expectedFeatures, $expectedNegative);

        $this->assertSame($expectedPath, $actual->path());
        $this->assertSame($expectedContent, $actual->content());
        $this->assertSame($expectedFlags, $actual->flags());
        $this->assertSame($expectedIncludes, $actual->includes());
        $this->assertSame($expectedFeatures, $actual->features());
        $this->assertSame($expectedNegative, $actual->negative());
    }
}
