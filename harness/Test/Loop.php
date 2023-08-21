<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Test;

use Fiber;
use Oru\EcmaScript\Harness\Contracts\Printer;
use Oru\EcmaScript\Harness\Contracts\TestResult;
use Oru\EcmaScript\Harness\Test\Exception\ReinitializedLoopException;
use Oru\EcmaScript\Harness\Test\Exception\UninitializedLoopException;

final class Loop
{
    /** @var Fiber[] $fibers */
    private array $fibers = [];

    /** @var TestResult[] $testResults */
    private array $testResults = [];

    private function __construct(
        private readonly Printer $printer,
    ) {
    }

    private static ?Loop $loop = null;

    public static function initialize(Printer $printer): void
    {
        if (static::$loop) {
            throw new ReinitializedLoopException();
        }

        static::$loop = new static($printer);
    }

    public static function get(): static
    {
        return static::$loop
            ?? new UninitializedLoopException();
    }

    public function add(Fiber $fiber): void
    {
        $fiber->start();
        if ($fiber->isSuspended()) {
            $this->fibers[] = $fiber;
        }
    }

    public function run(): void
    {
        while ($this->fibers !== []) {
            foreach ($this->fibers as $key => $fiber) {
                $fiber->resume();
                if ($fiber->isTerminated()) {
                    unset($this->fibers[$key]);
                }
            }
        }
    }

    public function addResult(TestResult $result): void
    {
        $this->testResults[] = $result;
        $this->printer->step($result->state());
    }
}
