<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Test;

use Oru\EcmaScript\Core\Contracts\Engine;
use Oru\EcmaScript\Core\Contracts\Values\UndefinedValue;
use Oru\EcmaScript\Harness\Contracts\Printer;
use Oru\EcmaScript\Harness\Test\BaseTestRunner;
use Oru\EcmaScript\Harness\Test\GenericTestResult;
use Oru\EcmaScript\Harness\Test\LinearTestRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LinearTestRunner::class)]
#[CoversClass(BaseTestRunner::class)]
#[UsesClass(GenericTestResult::class)]
final class LinearTestRunnerTest extends BaseTestRunnerTestAbstract
{
    protected function createTestRunner(Engine $engine): BaseTestRunner
    {
        return new LinearTestRunner(
            $engine,
            $this->createMock(Printer::class)
        );
    }
}
