<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Loop;

use Fiber;
use Oru\EcmaScript\Harness\Contracts\Loop;
use Oru\EcmaScript\Harness\Contracts\Printer;
use Oru\EcmaScript\Harness\Contracts\TestResult;

use function array_shift;
use function count;

final class TaskLoop implements Loop
{
    /** @var Fiber[] $activeFibers */
    private array $activeFibers = [];

    /** @var Fiber[] $pendingFibers */
    private array $pendingFibers = [];

    /** @var TestResult[] $testResults */
    private array $testResults = [];

    private int $concurrency = 8;

    public function __construct(
        private readonly Printer $printer,
    ) {
    }

    /**
     * @param callable():void $task
     */
    public function addTask(callable $task): void
    {
        $fiber = new Fiber($task);
        $this->pendingFibers[] = $fiber;
    }

    public function run(): void
    {
        while (
            $this->activeFibers !== []
            || $this->pendingFibers !== []
        ) {
            $this->fillActiveList();

            foreach ($this->activeFibers as $key => $fiber) {
                $fiber->resume();
                if ($fiber->isTerminated()) {
                    unset($this->activeFibers[$key]);
                    $this->fillActiveList();
                }
            }
        }
    }

    private function fillActiveList(): void
    {
        while (count($this->activeFibers) < $this->concurrency) {
            if ($this->pendingFibers === []) {
                break;
            }

            $fiber = array_shift($this->pendingFibers);
            $fiber->start();
            if ($fiber->isSuspended()) {
                $this->activeFibers[] = $fiber;
            }
        }
    }

    public function addResult(TestResult $result): void
    {
        $this->testResults[] = $result;
        $this->printer->step($result->state());
    }
}
