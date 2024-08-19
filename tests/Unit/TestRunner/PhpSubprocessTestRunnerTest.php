<?php

/**
 * Copyright (c) 2023-2024, Felix Jahn
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * SPDX-License-Identifier: BSD-3-Clause
 *
 * @see https://github.com/oruborus/harness262
 */

declare(strict_types=1);

namespace Tests\Unit\TestRunner;

use ErrorException;
use Generator;
use Oru\Harness\Contracts\Command;
use Oru\Harness\Contracts\ImplicitStrictness;
use Oru\Harness\Contracts\Loop;
use Oru\Harness\Contracts\Printer;
use Oru\Harness\Contracts\StopOnCharacteristic;
use Oru\Harness\Contracts\Subprocess;
use Oru\Harness\Contracts\SubprocessFactory;
use Oru\Harness\Contracts\TestCase;
use Oru\Harness\Contracts\TestResult;
use Oru\Harness\Contracts\TestResultState;
use Oru\Harness\Contracts\TestRunner;
use Oru\Harness\Contracts\TestRunnerMode;
use Oru\Harness\Frontmatter\GenericFrontmatter;
use Oru\Harness\Loop\FiberTask;
use Oru\Harness\TestCase\GenericTestCase;
use Oru\Harness\TestRunner\PhpSubprocessTestRunner;
use Oru\Harness\TestSuite\GenericTestSuite;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Tests\Utility\Loop\SimpleLoop;

use function array_shift;

#[CoversClass(PhpSubprocessTestRunner::class)]
#[UsesClass(FiberTask::class)]
final class PhpSubprocessTestRunnerTest extends PHPUnitTestCase
{
    private function createTestRunner(
        ?Printer $printer = null,
        ?Command $command = null,
        ?Loop $loop = null,
        ?SubprocessFactory $subprocessFactory = null,
    ): TestRunner {
        return new PhpSubprocessTestRunner(
            $printer ?? $this->createStub(Printer::class),
            $command ?? $this->createStub(Command::class),
            $loop ?? $this->createStub(Loop::class),
            $subprocessFactory ?? $this->createStub(SubprocessFactory::class),
        );
    }

    #[Test]
    public function addsTaskToProvidedLoop(): void
    {
        $loopMock = $this->createMock(Loop::class);
        $loopMock->expects($this->once())->method('add');
        $testCaseStub = $this->createStub(TestCase::class);

        $testRunner = $this->createTestRunner(loop: $loopMock);
        $testRunner->add($testCaseStub);
    }

    #[Test]
    public function relaysThrowablesThrownInTestCase(): void
    {
        $expected = new ErrorException('THROWN IN TEST');
        $this->expectExceptionObject($expected);

        $subprocessStub = $this->createStub(Subprocess::class);
        $subprocessStub->method('run')->willThrowException($expected);
        $subprocessFactoryStub = $this->createConfiguredStub(SubprocessFactory::class, ['make' => $subprocessStub]);
        $testCaseStub = $this->createStub(TestCase::class);
        $testRunner = $this->createTestRunner(
            loop: new SimpleLoop(),
            subprocessFactory: $subprocessFactoryStub,
        );

        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    public function informsProvidedPrinterAboutCompletedTestCase(): void
    {
        $subprocessFactoryStub = $this->createStub(SubprocessFactory::class);
        $subprocessFactoryStub->method('make')->willReturnCallback(function () use (&$results) {
            return $this->createConfiguredStub(Subprocess::class, [
                'run' => $this->createConfiguredStub(TestResult::class, [
                    'state' => TestResultState::Success,
                ]),
            ]);
        });
        $printerMock = $this->createMock(Printer::class);
        $printerMock->expects($this->once())->method('step');
        $testCaseStub = $this->createStub(TestCase::class);
        $testRunner = $this->createTestRunner(
            printer: $printerMock,
            subprocessFactory: $subprocessFactoryStub,
            loop: new SimpleLoop(),
        );

        $testRunner->add($testCaseStub);
        $testRunner->run();
    }

    #[Test]
    #[DataProvider('provideContents')]
    public function stopsExecutionWhenStopOnCharacteristicIsMet(StopOnCharacteristic $stopOnCharacteristic, array $results, int $expectedCount): void
    {
        $testCases = array_map(
            static fn(): TestCase => new GenericTestCase(
                '',
                '',
                new GenericFrontmatter('description: x'),
                new GenericTestSuite([], false, 4, TestRunnerMode::Async, $stopOnCharacteristic, 123),
                ImplicitStrictness::Unknown,
            ),
            $results
        );
        $subprocessFactoryStub = $this->createStub(SubprocessFactory::class);
        $subprocessFactoryStub->method('make')->willReturnCallback(function () use (&$results) {
            return $this->createConfiguredStub(Subprocess::class, [
                'run' => $this->createConfiguredStub(TestResult::class, [
                    'state' => array_shift($results),
                ]),
            ]);
        });
        $testRunner = $this->createTestRunner(
            loop: new SimpleLoop(),
            subprocessFactory: $subprocessFactoryStub,
        );
        foreach ($testCases as $testCase) {
            $testRunner->add($testCase);
        }

        $actual = $testRunner->run();

        $this->assertCount($expectedCount, $actual);
    }

    public static function provideContents(): Generator
    {
        yield 'nothing'  => [StopOnCharacteristic::Nothing, [TestResultState::Success, TestResultState::Success, TestResultState::Fail, TestResultState::Error, TestResultState::Success, TestResultState::Success], 6];
        yield 'error'    => [StopOnCharacteristic::Error, [TestResultState::Success, TestResultState::Success, TestResultState::Fail, TestResultState::Error, TestResultState::Success, TestResultState::Success], 4];
        yield 'failure'  => [StopOnCharacteristic::Failure, [TestResultState::Success, TestResultState::Success, TestResultState::Fail, TestResultState::Error, TestResultState::Success, TestResultState::Success], 3];
        yield 'defect 1' => [StopOnCharacteristic::Defect, [TestResultState::Success, TestResultState::Success, TestResultState::Fail, TestResultState::Error, TestResultState::Success, TestResultState::Success], 3];
        yield 'defect 2' => [StopOnCharacteristic::Defect, [TestResultState::Success, TestResultState::Success, TestResultState::Error, TestResultState::Fail, TestResultState::Success, TestResultState::Success], 3];
    }
}
