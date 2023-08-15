<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Test;

use Oru\EcmaScript\Harness\Contracts\Frontmatter;
use Oru\EcmaScript\Harness\Test\GenericTestConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericTestConfig::class)]
final class GenericTestConfigTest extends TestCase
{
    #[Test]
    public function actsAsValueObject(): void
    {
        $expectedPath        = 'path/to/file';
        $expectedContent     = 'CONTENT';
        $expectedFrontmatter = $this->createMock(Frontmatter::class);

        $actual = new GenericTestConfig($expectedPath, $expectedContent, $expectedFrontmatter);

        $this->assertSame($expectedPath, $actual->path());
        $this->assertSame($expectedContent, $actual->content());
        $this->assertSame($expectedFrontmatter, $actual->frontmatter());
    }
}
